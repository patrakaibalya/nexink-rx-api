<?php

namespace App\Http\Requests\Procedure;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload one or more procedure documents in a single request.
 * `documents[]` is the multi-file field; the older single `document`
 * field is still accepted so existing clients keep working.
 */
class ProcedureDocumentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required_without:documents',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],

            'documents' => [
                'required_without:document',
                'array',
                'min:1',
                'max:10',
            ],

            'documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required_without' => 'Please choose at least one file to upload.',
            'documents.required_without' => 'Please choose at least one file to upload.',
            'documents.max' => 'You can upload up to 10 files at a time.',
            'documents.*.mimes' => 'Each file must be a PDF or an image (JPG, PNG, WEBP).',
            'documents.*.max' => 'Each file must be 10 MB or smaller.',
        ];
    }

    /**
     * @return \Illuminate\Http\UploadedFile[]
     */
    public function uploadedFiles(): array
    {
        return $this->file('documents') ?? [$this->file('document')];
    }
}
