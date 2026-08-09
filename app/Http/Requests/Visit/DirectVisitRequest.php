<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;

class DirectVisitRequest extends FormRequest
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

            'chief_complaint' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
