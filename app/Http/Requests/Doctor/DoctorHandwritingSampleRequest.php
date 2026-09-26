<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class DoctorHandwritingSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bulk mode: ink_file[] (+ page_number[]) uploading several pages in
     * one request. Detected from the shape of the uploaded file(s) so a
     * single-file client's request is completely unaffected.
     */
    public function isBulk(): bool
    {
        return is_array($this->file('ink_file'));
    }

    public function rules(): array
    {
        if ($this->isBulk()) {
            return [
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
                    'required',
                    'array',
                    'min:1',
                ],

                'page_number.*' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'ink_file' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'ink_file.*' => [
                    'file',
                    'max:10240',
                ],
            ];
        }

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

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (!$this->isBulk()) {
                return;
            }

            $files = $this->file('ink_file') ?? [];
            $pageNumbers = $this->input('page_number') ?? [];

            if (count($files) !== count($pageNumbers)) {
                $validator->errors()->add(
                    'page_number',
                    'page_number and ink_file must have the same number of entries.'
                );

                return;
            }

            if (count(array_unique($pageNumbers)) !== count($pageNumbers)) {
                $validator->errors()->add(
                    'page_number',
                    'page_number values must be unique.'
                );
            }
        });
    }
}
