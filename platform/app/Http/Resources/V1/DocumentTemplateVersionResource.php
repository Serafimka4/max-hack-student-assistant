<?php

namespace App\Http\Resources\V1;

use App\Models\DocumentTemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentTemplateVersion */
class DocumentTemplateVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            /** Временная ссылка (30 минут), в MAX открывается через WebApp.downloadFile. */
            'download_url' => $this->temporaryDownloadUrl(),
            'created_at' => $this->created_at,
        ];
    }
}
