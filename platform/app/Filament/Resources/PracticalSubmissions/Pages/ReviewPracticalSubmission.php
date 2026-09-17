<?php

namespace App\Filament\Resources\PracticalSubmissions\Pages;

use App\Actions\Practical\ReviewPractical;
use App\Exceptions\DomainRuleException;
use App\Filament\Resources\PracticalSubmissions\PracticalSubmissionResource;
use App\Models\PracticalSubmission;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ReviewPracticalSubmission extends EditRecord
{
    protected static string $resource = PracticalSubmissionResource::class;

    protected static ?string $title = 'Проверка практического задания';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var PracticalSubmission $record */
        $record = $this->getRecord();
        $latest = $record->latestReview;

        return [...$data, 'scores' => $latest?->scores ?? [], 'comment' => $latest?->comment];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PracticalSubmission $record */
        try {
            app(ReviewPractical::class)($record, Auth::user(), array_values($data['scores'] ?? []), $data['comment'] ?? null);
        } catch (DomainRuleException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
            $this->halt();
        }

        return $record->refresh();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Оценка сохранена, уровни студента пересчитаны';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Сохранить оценку');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
