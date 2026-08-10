<?php

namespace App\Http\Requests\Investigation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestigationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'ordered',
                    'in_progress',
                    'completed',
                    'cancelled',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'items' => [
                'sometimes',
                'array',
                'min:1',
            ],

            'items.*.test_name' => [
                'required',
                'string',
                'max:500',
            ],

            'items.*.test_type' => [
                'required',
                'string',
                Rule::in([
                    'pathology',
                    'radiology',
                    'other',
                ]),
            ],

            'items.*.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
