<?php

namespace App\Http\Requests\Investigation;

use Illuminate\Foundation\Http\FormRequest;

class InvestigationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],
        ];
    }
}
