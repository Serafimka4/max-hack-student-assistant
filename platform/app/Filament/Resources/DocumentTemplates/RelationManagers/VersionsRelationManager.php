<?php

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Actions\Templates\StoreTemplateVersion;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Версии файла';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->modelLabel('версия')
            ->pluralModelLabel('версии')
            ->columns([
                TextColumn::make('version')->label('Версия')->prefix('v')->weight('bold'),
                TextColumn::make('original_name')->label('Файл'),
                TextColumn::make('size')->label('Размер')->formatStateUsing(fn (int $state) => Number::fileSize($state)),
                TextColumn::make('uploader.name')->label('Загрузил')->placeholder('—'),
                TextColumn::make('created_at')->label('Загружена')->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Загрузить новую версию')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn () => Auth::user()->can('update', $this->getOwnerRecord()))
                    ->schema([
                        FileUpload::make('file')
                            ->label('Файл шаблона')
                            ->helperText('PDF, DOC(X), ODT, XLS(X), до 20 МБ. Предыдущие версии сохранятся.')
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes(StoreTemplateVersion::MIME_TYPES)
                            ->maxSize(StoreTemplateVersion::MAX_KILOBYTES),
                    ])
                    ->action(function (array $data, StoreTemplateVersion $store) {
                        /** @var DocumentTemplate $template */
                        $template = $this->getOwnerRecord();
                        /** @var TemporaryUploadedFile $file */
                        $file = $data['file'];
                        $version = $store($template, $file, Auth::user());

                        Notification::make()->success()->title("Загружена версия v{$version->version}")->send();
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Скачать')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (DocumentTemplateVersion $record) => $record->temporaryDownloadUrl(5))
                    ->openUrlInNewTab(),
            ]);
    }
}
