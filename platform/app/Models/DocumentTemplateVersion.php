<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

#[Fillable(['version', 'disk', 'path', 'original_name', 'mime_type', 'size', 'uploaded_by'])]
class DocumentTemplateVersion extends Model
{
    /** @return BelongsTo<DocumentTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Временная подписанная ссылка: WebApp.downloadFile в MAX не передаёт сессию.
     */
    public function temporaryDownloadUrl(int $minutes = 30): string
    {
        return URL::temporarySignedRoute('templates.download', now()->addMinutes($minutes), ['version' => $this->id]);
    }
}
