<?php

namespace App\Http\Requests\Visit;

use Illuminate\Foundation\Http\FormRequest;

class VisitEmergencyCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinical_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:20000',
            ],
        ];
    }
}
