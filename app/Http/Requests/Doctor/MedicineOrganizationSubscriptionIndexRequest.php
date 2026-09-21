<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicineOrganizationSubscriptionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in([
                    'pending',
                    'approved',
                    'rejected',
                    'cancelled',
                    'expired',
                ]),
            ],
        ];
    }
}
