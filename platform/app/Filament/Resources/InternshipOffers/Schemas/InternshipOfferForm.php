<?php

namespace App\Filament\Resources\InternshipOffers\Schemas;

use App\Enums\OfferDirection;
use App\Enums\OrganizationType;
use App\Enums\SkillLevel;
use App\Models\Organization;
use App\Models\Skill;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InternshipOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        $organization = Filament::getTenant();

        return $schema->components([
            Section::make('Предложение')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')->label('Название')->required()->maxLength(255),
                    Select::make('direction')->label('Направление')->options(OfferDirection::class)->required(),
                    TextInput::make('company_name')->label('Организация')->required()->maxLength(255)
                        ->helperText('Как увидит студент.'),
                    Select::make('employer_id')->label('Кабинет работодателя')->searchable()
                        ->options(fn () => Organization::where('type', OrganizationType::Employer)->orderBy('name')->pluck('name', 'id'))
                        ->helperText('Если связать, работодатель увидит откликнувшихся и их разрешённые результаты.'),
                    TextInput::make('format')->label('Формат')->required()->placeholder('Гибрид, офис, удалённо'),
                    TextInput::make('places')->label('Мест')->numeric()->minValue(1)->default(1)->required(),
                    Grid::make(3)->schema([
                        TextInput::make('starts_on')->label('Начало')->type('date'),
                        TextInput::make('ends_on')->label('Окончание')->type('date'),
                        TextInput::make('apply_until')->label('Приём заявок до')->type('date'),
                    ]),
                    Repeater::make('tasks')
                        ->label('Задачи практики')
                        ->simple(TextInput::make('task')->required()->maxLength(500))
                        ->addActionLabel('Добавить задачу')
                        ->columnSpanFull(),
                    TextInput::make('contact')->label('Контактный ответственный')->maxLength(255),
                    Toggle::make('is_published')->label('Опубликовано для студентов'),
                ]),

            Section::make('Требуемые навыки')
                ->description('Студент увидит, что подтверждено диагностикой, а что нужно уточнить на собеседовании.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('requirements')
                        ->label('')
                        ->addActionLabel('Добавить навык')
                        ->columns(2)
                        ->defaultItems(0)
                        ->schema([
                            Select::make('skill_id')->label('Навык')->required()->searchable()->distinct()
                                ->options(fn () => Skill::availableTo($organization)->orderBy('direction')->orderBy('title')->get()
                                    ->mapWithKeys(fn (Skill $s) => [$s->id => "{$s->title} · {$s->direction}"])),
                            Select::make('level')->label('Требуемый уровень')->options(SkillLevel::class)
                                ->default(SkillLevel::Basic->value)->required(),
                        ]),
                ]),
        ]);
    }
}
