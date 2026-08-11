<?php

namespace App\Http\Requests\Investigation;

use Illuminate\Foundation\Http\FormRequest;

class InvestigationCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.id' => [
                'required',
                'integer',
                'distinct',
            ],

            'items.*.result' => [
                'nullable',
                'string',
            ],

            'items.*.result_unit' => [
                'nullable',
                'string',
                'max:100',
            ],

            'items.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.result_notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
