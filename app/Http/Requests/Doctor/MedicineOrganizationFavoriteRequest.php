<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class MedicineOrganizationFavoriteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_favorite')) {
            $value = $this->input('is_favorite');

            if ($value === 'true') {
                $this->merge([
                    'is_favorite' => true,
                ]);
            }

            if ($value === 'false') {
                $this->merge([
                    'is_favorite' => false,
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
            'is_favorite' => [
                'required',
                'boolean',
            ],
        ];
    }
}
