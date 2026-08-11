<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $doctorId = $this->user()->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('doctor_accounts', 'mobile')
                    ->ignore($doctorId),
            ],

            'specialization' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'medical_license_number' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'doctor_accounts',
                    'medical_license_number'
                )->ignore($doctorId),
            ],
        ];
    }
}
