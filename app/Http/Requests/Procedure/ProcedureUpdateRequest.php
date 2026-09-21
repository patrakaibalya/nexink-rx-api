<?php

namespace App\Http\Requests\Procedure;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcedureUpdateRequest extends FormRequest
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

            'items.*.procedure_name' => [
                'required',
                'string',
                'max:500',
            ],

            'items.*.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
