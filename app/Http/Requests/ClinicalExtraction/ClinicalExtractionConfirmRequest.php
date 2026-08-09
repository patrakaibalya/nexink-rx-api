<?php

namespace App\Http\Requests\ClinicalExtraction;

use Illuminate\Foundation\Http\FormRequest;

class ClinicalExtractionConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnoses' => [
                'sometimes',
                'array',
            ],

            'medicines' => [
                'sometimes',
                'array',
            ],

            'investigations' => [
                'sometimes',
                'array',
            ],

            'instructions' => [
                'sometimes',
                'array',
            ],

            'follow_up' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'other' => [
                'sometimes',
                'array',
            ],
        ];
    }
}
