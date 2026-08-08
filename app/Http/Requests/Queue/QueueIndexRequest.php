<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueIndexRequest extends FormRequest
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
                    'waiting',
                    'called',
                    'consulting',
                    'completed',
                    'cancelled',
                    'no_show',
                ]),
            ],
        ];
    }
}
