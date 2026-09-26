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
                'in:prescription',
            ],

            'prescription_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'page_number' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'ink_file' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }
}
