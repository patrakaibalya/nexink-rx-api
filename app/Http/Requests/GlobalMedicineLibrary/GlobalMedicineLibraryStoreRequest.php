<?php

namespace App\Http\Requests\GlobalMedicineLibrary;

use Illuminate\Foundation\Http\FormRequest;

class GlobalMedicineLibraryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medicine_name' => [
                'required',
                'string',
                'max:255',
            ],

            'generic_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'composition' => [
                'nullable',
                'string',
            ],

            'strength' => [
                'nullable',
                'string',
                'max:255',
            ],

            'dosage_form' => [
                'nullable',
                'string',
                'max:255',
            ],

            'manufacturer' => [
                'nullable',
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

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
