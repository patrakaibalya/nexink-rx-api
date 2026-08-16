<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class CorrectDoctorHandwritingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw_recognized_text' => [
                'required',
                'string',
                'max:10000',
            ],

            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
        ];
    }
}
