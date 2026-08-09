<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueHistoryRequest extends FormRequest
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

            'from' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
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
