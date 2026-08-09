<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic_id' => [
                'nullable',
                'integer',
            ],

            'date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'scheduled',
                    'confirmed',
                    'arrived',
                    'completed',
                    'cancelled',
                    'no_show',
                ]),
            ],

            'patient_id' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
