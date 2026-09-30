<?php

namespace App\Http\Requests\Investigation;

use Illuminate\Foundation\Http\FormRequest;

class InvestigationIndexRequest extends FormRequest
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
                'exists:doctor.clinics,id',
            ],

            'patient_id' => [
                'nullable',
                'integer',
                'exists:doctor.patients,id',
            ],

            'visit_id' => [
                'nullable',
                'integer',
                'exists:doctor.visits,id',
            ],

            'status' => [
                'nullable',
                'string',
                'in:ordered,in_progress,completed,cancelled',
            ],

            'date' => [
                'nullable',
                'date',
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
