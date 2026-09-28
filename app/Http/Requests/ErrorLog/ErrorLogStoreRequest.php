<?php

namespace App\Http\Requests\ErrorLog;

use App\Models\ErrorLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ErrorLogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => [
                'required',
                'string',
                Rule::in(['android', 'ios', 'web']),
            ],

            'screen_name' => [
                'required',
                'string',
                'max:255',
            ],

            'function_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'error_description' => [
                'required',
                'string',
                'max:10000',
            ],

            'error_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'stack_trace' => [
                'nullable',
                'string',
                'max:65000',
            ],

            'severity' => [
                'nullable',
                'string',
                Rule::in(ErrorLog::SEVERITIES),
            ],

            'api_endpoint' => [
                'nullable',
                'string',
                'max:500',
            ],

            'http_method' => [
                'nullable',
                'string',
                'max:10',
            ],

            'http_status' => [
                'nullable',
                'integer',
                'min:0',
                'max:999',
            ],

            'request_payload' => [
                'nullable',
                'array',
            ],

            'app_version' => [
                'nullable',
                'string',
                'max:50',
            ],

            'device_info' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
