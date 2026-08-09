<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => [
                'required',
                'integer',
            ],

            'patient_id' => [
                'required',
                'integer',
            ],

            'appointment_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'appointment_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'source' => [
                'nullable',
                Rule::in([
                    'web',
                    'patient',
                    'vapi',
                    'staff',
                ]),
            ],

            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
