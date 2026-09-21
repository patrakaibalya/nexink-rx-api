<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'gender' => [
                'nullable',
                'string',
                'max:50',
            ],

            'blood_group' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],

            'height' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],

            'is_diabetic' => [
                'nullable',
                'boolean',
            ],

            'diabetic_result' => [
                'nullable',
                'string',
                'max:255',
            ],

            'blood_pressure' => [
                'nullable',
                'string',
                Rule::in(['high', 'low', 'normal']),
            ],

            'blood_pressure_result' => [
                'nullable',
                'string',
                'max:255',
            ],

            'uid_aadhar_no' => [
                'nullable',
                'string',
                'max:20',
            ],

            'thyroid_result' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
