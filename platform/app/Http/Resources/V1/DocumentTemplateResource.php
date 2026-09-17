<?php

namespace App\Http\Resources\V1;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentTemplate */
class DocumentTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'title' => $this->title,
            'category' => $this->category,
            'purpose' => $this->purpose,
            'instructions' => $this->instructions,
            'submission' => $this->submission,
            'department' => $this->department,
            'is_published' => $this->is_published,
            'current_version' => DocumentTemplateVersionResource::make($this->whenLoaded('currentVersion')),
            'updated_at' => $this->updated_at,
        ];
    }
}
