<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VisitStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => [
                'nullable',
                'integer',
            ],

            'queue_id' => [
                'nullable',
                'integer',
            ],

            'clinic_id' => [
                'required',
                'integer',
            ],

            'patient_id' => [
                'required',
                'integer',
            ],

            'chief_complaint' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (
                !$this->filled('appointment_id')
                && !$this->filled('queue_id')
            ) {
                $validator->errors()->add(
                    'visit',
                    'Appointment ID or Queue ID is required.'
                );
            }
        });
    }
}
