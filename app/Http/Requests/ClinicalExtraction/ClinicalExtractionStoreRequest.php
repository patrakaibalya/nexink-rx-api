<?php

namespace App\Http\Requests\ClinicalExtraction;

use Illuminate\Foundation\Http\FormRequest;

class ClinicalExtractionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'finalized_data' => [
                'required',
                'array',
            ],
        ];
    }
}
