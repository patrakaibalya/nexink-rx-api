<?php

namespace App\Http\Requests\Patient;

use App\Models\PatientVital;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PatientVitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_id' => [
                'nullable',
                'integer',
                'exists:doctor.visits,id',
            ],

            'clinic_id' => [
                'nullable',
                'integer',
                'exists:doctor.clinics,id',
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

            'blood_pressure_result' => [
                'nullable',
                'string',
                'max:100',
            ],

            'pulse' => [
                'nullable',
                'string',
                'max:50',
            ],

            'temperature' => [
                'nullable',
                'string',
                'max:50',
            ],

            'spo2' => [
                'nullable',
                'string',
                'max:50',
            ],

            'blood_sugar' => [
                'nullable',
                'string',
                'max:100',
            ],

            'diabetic_result' => [
                'nullable',
                'string',
                'max:255',
            ],

            'thyroid_result' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],

            'recorded_by' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $hasReading = collect(PatientVital::READING_FIELDS)
                    ->contains(fn ($field) => filled($this->input($field)));

                if (!$hasReading) {
                    $validator->errors()->add(
                        'vitals',
                        'Enter at least one reading (weight, BP, pulse, sugar, etc.).'
                    );
                }
            },
        ];
    }
}
