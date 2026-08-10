<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrescriptionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'draft',
                    'final',
                    'cancelled',
                ]),
            ],

            'items' => [
                'sometimes',
                'array',
                'min:1',
            ],

            'items.*.medicine_name' => [
                'required',
                'string',
                'max:500',
            ],

            'items.*.dosage' => [
                'nullable',
                'string',
                'max:200',
            ],

            'items.*.frequency' => [
                'nullable',
                'string',
                'max:200',
            ],

            'items.*.duration' => [
                'nullable',
                'string',
                'max:200',
            ],

            'items.*.route' => [
                'nullable',
                'string',
                'max:100',
            ],

            'items.*.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
