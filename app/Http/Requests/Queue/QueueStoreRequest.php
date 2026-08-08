<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueStoreRequest extends FormRequest
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

            'source' => [
                'required',
                Rule::in([
                    'web',
                    'vapi',
                    'patient',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}
