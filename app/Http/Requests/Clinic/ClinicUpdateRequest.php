<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class ClinicUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'city' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'pincode' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'mobile' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            'timezone' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'consultation_fee' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'active_pricing_organization_id' => [
                'sometimes',
                'nullable',
                'integer',
            ],
        ];
    }
}
