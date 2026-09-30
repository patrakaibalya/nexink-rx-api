<?php

namespace App\Http\Requests\Report;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OrganizationSalesReportRequest extends FormRequest
{
    /**
     * Longest range one report may cover, to keep the response bounded.
     */
    private const MAX_RANGE_DAYS = 366;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'to_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from_date',
            ],

            'doctor_id' => [
                'nullable',
                'integer',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $days = Carbon::parse($this->input('from_date'))
                    ->diffInDays(Carbon::parse($this->input('to_date')));

                if ($days > self::MAX_RANGE_DAYS) {
                    $validator->errors()->add(
                        'to_date',
                        'A report can cover at most ' . self::MAX_RANGE_DAYS . ' days.'
                    );
                }
            },
        ];
    }
}
