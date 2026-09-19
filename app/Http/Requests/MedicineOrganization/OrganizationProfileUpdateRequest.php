<?php

namespace App\Http\Requests\MedicineOrganization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()->id;

        return [
            'organization_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('medicine_organizations', 'email')
                    ->ignore($organizationId),
            ],

            'mobile' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('medicine_organizations', 'mobile')
                    ->ignore($organizationId),
            ],

            'contact_person' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
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
                'max:10',
            ],

            'gst_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('medicine_organizations', 'gst_number')
                    ->ignore($organizationId),
            ],

            'pan_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('medicine_organizations', 'pan_number')
                    ->ignore($organizationId),
            ],

            'drug_license_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('medicine_organizations', 'drug_license_number')
                    ->ignore($organizationId),
            ],
        ];
    }
}
