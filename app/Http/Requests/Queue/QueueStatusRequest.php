<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
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
