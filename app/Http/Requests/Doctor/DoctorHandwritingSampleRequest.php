<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class DoctorHandwritingSampleRequest extends FormRequest
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

            'sample_type' => [
                'required',
                'string',
                'in:about_me,prescription',
            ],

            'prescription_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:sample_type,prescription',
                'prohibited_if:sample_type,about_me',
            ],

            'ink_file' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }
}
