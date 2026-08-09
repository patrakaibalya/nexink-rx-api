<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => [
                'sometimes',
                'required',
                'integer',
            ],

            'patient_id' => [
                'sometimes',
                'required',
                'integer',
            ],

            'appointment_date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
            ],

            'appointment_time' => [
                'sometimes',
                'nullable',
                'date_format:H:i',
            ],

            'source' => [
                'sometimes',
                Rule::in([
                    'web',
                    'patient',
                    'vapi',
                    'staff',
                ]),
            ],

            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
