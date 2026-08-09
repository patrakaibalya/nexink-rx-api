<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'appointment_time' => [
                'required',
                'date_format:H:i',
            ],
        ];
    }
}
