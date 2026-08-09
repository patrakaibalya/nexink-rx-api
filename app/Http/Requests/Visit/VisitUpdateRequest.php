<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;

class VisitUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chief_complaint' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'clinical_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:20000',
            ],
            'diagnosis' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}
