<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MaxAuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /** Строка window.WebApp.initData из MAX Bridge без изменений. */
            'init_data' => ['required', 'string', 'max:8192'],
        ];
    }
}
