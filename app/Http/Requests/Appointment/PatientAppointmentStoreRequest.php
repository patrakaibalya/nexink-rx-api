<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class PatientAppointmentStoreRequest extends FormRequest
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
                'required',
                'date_format:H:i',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
