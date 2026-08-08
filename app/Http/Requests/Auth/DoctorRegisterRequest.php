<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DoctorRegisterRequest extends FormRequest
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

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:doctor_accounts,email',
            ],

            'mobile' => [
                'required',
                'string',
                'max:20',
                'unique:doctor_accounts,mobile',
            ],

            'specialization' => [
                'nullable',
                'string',
                'max:255',
            ],

            'medical_license_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }
}
