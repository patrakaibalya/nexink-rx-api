<?php

namespace App\Http\Requests\ClinicalExtraction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClinicalExtractionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Schema
            |--------------------------------------------------------------------------
            */

            'schema_version' => [
                'required',
                'string',
                Rule::in(['1.0']),
            ],

            'extraction_status' => [
                'required',
                'string',
                Rule::in([
                    'completed',
                    'partial',
                    'failed',
                ]),
            ],

            'confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Diagnoses
            |--------------------------------------------------------------------------
            */

            'diagnoses' => [
                'present',
                'array',
            ],

            'diagnoses.*.name' => [
                'required',
                'string',
                'max:500',
            ],

            'diagnoses.*.confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Medicines
            |--------------------------------------------------------------------------
            */

            'medicines' => [
                'present',
                'array',
            ],

            'medicines.*.name' => [
                'required',
                'string',
                'max:500',
            ],

            'medicines.*.strength' => [
                'nullable',
                'string',
                'max:100',
            ],

            'medicines.*.dosage' => [
                'nullable',
                'string',
                'max:200',
            ],

            'medicines.*.frequency' => [
                'nullable',
                'string',
                'max:200',
            ],

            'medicines.*.duration' => [
                'nullable',
                'string',
                'max:200',
            ],

            'medicines.*.route' => [
                'nullable',
                'string',
                'max:100',
            ],

            'medicines.*.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'medicines.*.confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Investigations
            |--------------------------------------------------------------------------
            */

            'investigations' => [
                'present',
                'array',
            ],

            'investigations.*.name' => [
                'required',
                'string',
                'max:500',
            ],

            'investigations.*.type' => [
                'required',
                'string',
                Rule::in([
                    'pathology',
                    'radiology',
                    'other',
                ]),
            ],

            'investigations.*.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'investigations.*.confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Instructions
            |--------------------------------------------------------------------------
            */

            'instructions' => [
                'present',
                'array',
            ],

            'instructions.*.text' => [
                'required',
                'string',
                'max:5000',
            ],

            'instructions.*.confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Follow Up
            |--------------------------------------------------------------------------
            */

            'follow_up' => [
                'nullable',
                'array',
            ],

            'follow_up.value' => [
                'required_with:follow_up',
            ],

            'follow_up.unit' => [
                'required_with:follow_up',
                'string',
                'max:50',
            ],

            'follow_up.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'follow_up.confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Other
            |--------------------------------------------------------------------------
            */

            'other' => [
                'present',
                'array',
            ],

            'other.*.text' => [
                'required',
                'string',
                'max:5000',
            ],

            'other.*.confidence' => [
                'nullable',
                'numeric',
                'between:0,1',
            ],
        ];
    }
}
