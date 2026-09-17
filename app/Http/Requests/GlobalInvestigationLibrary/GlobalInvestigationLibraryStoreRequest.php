<?php

namespace App\Http\Requests\GlobalInvestigationLibrary;

use Illuminate\Foundation\Http\FormRequest;

class GlobalInvestigationLibraryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investigation_name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'unit' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'unit_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
