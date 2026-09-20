<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class PrescriptionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
                'in:draft,unverified,final,cancelled',
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
