<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'gender' => [
                'nullable',
                'string',
                Rule::in(['male', 'female', 'other']),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
