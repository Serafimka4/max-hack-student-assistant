<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use App\Actions\Assessments\CreateAssessmentVersion;
use App\Actions\Assessments\PublishAssessmentVersion;
use App\Actions\Assessments\SaveVersionQuestions;
use App\Enums\QuestionType;
use App\Exceptions\DomainRuleException;
use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Skill;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Версии и вопросы';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->modelLabel('версия')
            ->pluralModelLabel('версии')
            ->columns([
                TextColumn::make('version')->label('Версия')->prefix('v')->weight('bold'),
                TextColumn::make('questions_count')->label('Вопросов')->counts('questions'),
                TextColumn::make('basic_threshold')->label('Порог')->suffix('%'),
                IconColumn::make('practical_task')->label('Практика')->boolean()->state(fn (AssessmentVersion $record) => $record->hasPractical()),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->state(fn (AssessmentVersion $record) => $record->isPublished() ? 'Опубликована' : 'Черновик')
                    ->color(fn (AssessmentVersion $record) => $record->isPublished() ? 'success' : 'gray'),
                TextColumn::make('published_at')->label('Дата публикации')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextColumn::make('notes')->label('Изменения')->limit(60)->placeholder('—'),
            ])
            ->headerActions([
                Action::make('newVersion')
                    ->label('Новая версия')
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn () => $this->canManage())
                    ->modalDescription('Черновик создаётся с вопросами последней версии. Опубликованные версии не изменятся.')
                    ->schema([
                        Textarea::make('notes')->label('Что изменится')->rows(2),
                    ])
                    ->action(function (array $data, CreateAssessmentVersion $create) {
                        $version = $create($this->assessment(), notes: $data['notes'] ?? null);
                        Notification::make()->success()->title("Создан черновик v{$version->version}")->send();
                    }),
            ])
            ->recordActions([
                Action::make('questions')
                    ->label(fn (AssessmentVersion $record) => $record->isPublished() ? 'Вопросы' : 'Редактировать вопросы')
                    ->icon(fn (AssessmentVersion $record) => $record->isPublished() ? Heroicon::OutlinedEye : Heroicon::OutlinedPencilSquare)
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->modalHeading(fn (AssessmentVersion $record) => "Вопросы версии v{$record->version}")
                    ->modalSubmitAction(fn (AssessmentVersion $record, Action $action) => $this->editable($record) ? $action : false)
                    ->fillForm(fn (AssessmentVersion $record) => [
                        'notes' => $record->notes,
                        'basic_threshold' => $record->basic_threshold,
                        'min_questions_per_skill' => $record->min_questions_per_skill,
                        'applied_threshold' => $record->applied_threshold,
                        'confident_threshold' => $record->confident_threshold,
                        'practical_task' => $record->practical_task,
                        'practical_rubric' => $record->practical_rubric ?? [],
                        'questions' => $record->questions->map(fn ($q) => [
                            'skill_id' => $q->skill_id,
                            'type' => $q->type->value,
                            'prompt' => $q->prompt,
                            'code' => $q->code,
                            'options' => $q->options,
                            'correct_keys' => $q->correct_keys,
                            'points' => $q->points,
                        ])->all(),
                    ])
                    ->schema(fn (AssessmentVersion $record) => $this->questionsSchema(! $this->editable($record)))
                    ->action(function (AssessmentVersion $record, array $data, Action $action, SaveVersionQuestions $save) {
                        try {
                            $save($record, $data['questions'] ?? [], $data['notes'] ?? null, [
                                'basic_threshold' => (int) $data['basic_threshold'],
                                'min_questions_per_skill' => (int) $data['min_questions_per_skill'],
                                'applied_threshold' => (int) $data['applied_threshold'],
                                'confident_threshold' => (int) $data['confident_threshold'],
                                'practical_task' => $data['practical_task'] ?? null,
                                'practical_rubric' => $data['practical_rubric'] ?? [],
                            ]);
                        } catch (DomainRuleException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                            $action->halt();
                        }

                        Notification::make()->success()->title('Вопросы сохранены')->send();
                    }),
                Action::make('publish')
                    ->label('Опубликовать')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (AssessmentVersion $record) => $this->editable($record))
                    ->requiresConfirmation()
                    ->modalDescription('После публикации версию нельзя изменить. Студенты будут проходить её.')
                    ->action(function (AssessmentVersion $record, PublishAssessmentVersion $publish) {
                        try {
                            $publish($record);
                            Notification::make()->success()->title("Версия v{$record->version} опубликована")->send();
                        } catch (DomainRuleException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }

    /** @return array<int, mixed> */
    private function questionsSchema(bool $locked): array
    {
        $organization = Filament::getTenant();

        return [
            Textarea::make('notes')->label('Что изменилось в версии')->rows(2)->disabled($locked),
            Grid::make(2)->schema([
                TextInput::make('basic_threshold')->label('Порог базового уровня, %')->numeric()->minValue(1)->maxValue(100)
                    ->required()->disabled($locked)->helperText('Согласуйте с преподавателем и работодателем до отбора.'),
                TextInput::make('min_questions_per_skill')->label('Минимум вопросов на навык')->numeric()->minValue(1)->maxValue(20)
                    ->required()->disabled($locked)->helperText('Меньше — «недостаточно данных».'),
            ]),
            Repeater::make('questions')
                ->label('Вопросы')
                ->disabled($locked)
                ->addActionLabel('Добавить вопрос')
                ->collapsible()
                ->cloneable()
                ->reorderable()
                ->minItems(1)
                ->itemLabel(fn (array $state) => str($state['prompt'] ?? 'Новый вопрос')->limit(70))
                ->columns(3)
                ->schema([
                    Select::make('skill_id')
                        ->label('Навык')
                        ->options(fn () => Skill::availableTo($organization)->orderBy('title')->get()
                            ->mapWithKeys(fn (Skill $s) => [$s->id => "{$s->title} · {$s->direction}"]))
                        ->searchable()
                        ->required(),
                    Select::make('type')->label('Тип')->options(QuestionType::class)->default(QuestionType::Single->value)->required(),
                    TextInput::make('points')->label('Баллы')->numeric()->minValue(1)->maxValue(100)->default(1)->required(),
                    Textarea::make('prompt')->label('Формулировка')->rows(2)->required()->columnSpanFull(),
                    Textarea::make('code')->label('Фрагмент кода (необязательно)')->rows(3)->extraInputAttributes(['class' => 'font-mono'])->columnSpanFull(),
                    Repeater::make('options')
                        ->label('Варианты ответа')
                        ->addActionLabel('Добавить вариант')
                        ->minItems(2)
                        ->maxItems(10)
                        ->defaultItems(2)
                        ->columns(6)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('key')->label('Ключ')->required()->maxLength(16)->placeholder('A'),
                            TextInput::make('text')->label('Текст варианта')->required()->columnSpan(5),
                        ]),
                    TagsInput::make('correct_keys')
                        ->label('Правильные ключи')
                        ->placeholder('A')
                        ->helperText('Ключи правильных вариантов. Студенту не показываются.')
                        ->required()
                        ->columnSpanFull(),
                ]),
            Section::make('Практическое задание')
                ->description('Проверяется специалистом по рубрике. Только оно даёт «Прикладной» и «Уверенный» уровни и грейд «Junior».')
                ->collapsible()
                ->schema([
                    Textarea::make('practical_task')->label('Задание')->rows(4)->disabled($locked)
                        ->helperText('Пусто — тест без практической части.'),
                    Grid::make(2)->schema([
                        TextInput::make('applied_threshold')->label('Порог «Прикладного», %')->numeric()->minValue(1)->maxValue(100)->required()->disabled($locked),
                        TextInput::make('confident_threshold')->label('Порог «Уверенного», %')->numeric()->minValue(1)->maxValue(100)->required()->disabled($locked)
                            ->gte('applied_threshold'),
                    ]),
                    Repeater::make('practical_rubric')
                        ->label('Рубрика')
                        ->disabled($locked)
                        ->addActionLabel('Добавить критерий')
                        ->requiredWith('practical_task')
                        ->columns(6)
                        ->defaultItems(0)
                        ->schema([
                            Select::make('skill_id')->label('Навык')->columnSpan(2)->required()->searchable()
                                ->options(fn () => Skill::availableTo($organization)->orderBy('title')->pluck('title', 'id')),
                            TextInput::make('criterion')->label('Критерий')->columnSpan(3)->required()->maxLength(500),
                            TextInput::make('max_points')->label('Макс.')->numeric()->minValue(1)->maxValue(100)->default(2)->required(),
                        ]),
                ]),
        ];
    }

    private function assessment(): Assessment
    {
        /** @var Assessment */
        return $this->getOwnerRecord();
    }

    private function canManage(): bool
    {
        return Auth::user()->can('update', $this->assessment());
    }

    private function editable(AssessmentVersion $record): bool
    {
        return ! $record->isPublished() && $this->canManage();
    }
}
