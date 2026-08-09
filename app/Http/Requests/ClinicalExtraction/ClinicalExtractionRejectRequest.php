<?php

namespace App\Http\Requests\ClinicalExtraction;

use Illuminate\Foundation\Http\FormRequest;

class ClinicalExtractionRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ];
    }
}
