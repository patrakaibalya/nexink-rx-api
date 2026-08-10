<?php

namespace App\Http\Requests\MedicineOrganization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicineOrganizationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('medicine_organizations', 'email'),
            ],

            'mobile' => [
                'required',
                'string',
                'max:20',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
            ],
        ];
    }
}
