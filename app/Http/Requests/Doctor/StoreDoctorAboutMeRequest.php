<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorAboutMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tool_data' => [
                'nullable',
                'json',
            ],

            'raw_recognized_text' => [
                'nullable',
                'string',
            ],

            'final_corrected_text' => [
                'nullable',
                'string',
            ],

            'ink_file' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }
}
