<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;

class VisitIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => [
                'nullable',
                'date',
            ],

            'clinic_id' => [
                'nullable',
                'integer',
                'exists:doctor.clinics,id',
            ],

            'patient_id' => [
                'nullable',
                'integer',
                'exists:doctor.patients,id',
            ],

            'status' => [
                'nullable',
                'string',
                'in:in_progress,completed',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
