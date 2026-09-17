<?php

namespace App\Http\Requests\Api\V1;

use App\Actions\Templates\StoreTemplateVersion;
use Illuminate\Foundation\Http\FormRequest;

class TemplateVersionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /** PDF, DOC(X), ODT, XLS(X) до 20 МБ. */
            'file' => ['required', 'file', 'max:'.StoreTemplateVersion::MAX_KILOBYTES, 'mimetypes:'.implode(',', StoreTemplateVersion::MIME_TYPES)],
        ];
    }
}
