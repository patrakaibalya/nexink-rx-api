<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class ClinicPrescriptionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_width' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'page_height' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'header_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'header_width' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'header_height' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'footer_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'footer_width' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'footer_height' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
