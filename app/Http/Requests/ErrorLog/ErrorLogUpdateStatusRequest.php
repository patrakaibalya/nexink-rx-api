<?php

namespace App\Http\Requests\ErrorLog;

use App\Models\ErrorLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ErrorLogUpdateStatusRequest extends FormRequest
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
                'string',
                Rule::in(ErrorLog::STATUSES),
            ],

            'resolution_note' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
