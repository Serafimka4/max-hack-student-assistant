<?php

namespace App\Actions\Templates;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Добавляет новую версию файла шаблона. Старые версии сохраняются.
 */
class StoreTemplateVersion
{
    public const MAX_KILOBYTES = 20480;

    public const MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __invoke(DocumentTemplate $template, UploadedFile $file, ?User $uploader): DocumentTemplateVersion
    {
        $disk = config('filesystems.templates_disk');

        return DB::transaction(function () use ($template, $file, $uploader, $disk) {
            // Блокировка шаблона исключает одинаковый номер версии при параллельной загрузке.
            DocumentTemplate::whereKey($template->id)->lockForUpdate()->first();
            $next = (int) $template->versions()->max('version') + 1;

            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'template';
            $path = $file->storeAs(
                "templates/{$template->organization_id}/{$template->id}",
                "v{$next}-{$name}.".strtolower($file->getClientOriginalExtension()),
                $disk,
            );

            return $template->versions()->create([
                'version' => $next,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $uploader?->id,
            ]);
        });
    }
}
