<?php

namespace App\Http\Requests\MedicineOrganization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrescriptionShareStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['viewed', 'dispensed', 'cancelled']),
            ],
        ];
    }
}
