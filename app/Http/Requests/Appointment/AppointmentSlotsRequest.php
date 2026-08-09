<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentSlotsRequest extends FormRequest
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

            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }
}
