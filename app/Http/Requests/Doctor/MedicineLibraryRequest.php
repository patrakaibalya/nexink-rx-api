<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class MedicineLibraryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('favorites_only')) {
            $value = $this->input('favorites_only');

            if ($value === 'true') {
                $this->merge([
                    'favorites_only' => true,
                ]);
            }

            if ($value === 'false') {
                $this->merge([
                    'favorites_only' => false,
                ]);
            }
        }
    }
    
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
                'max:255',
            ],

            'organization_id' => [
                'nullable',
                'integer',
                'exists:medicine_organizations,id',
            ],

            'favorites_only' => [
                'nullable',
                'boolean',
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
