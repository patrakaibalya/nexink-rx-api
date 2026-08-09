<?php

namespace App\Http\Requests\Clinic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ClinicWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'working_hours' => [
                'required',
                'array',
                'size:7',
            ],

            'working_hours.*.day_of_week' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'working_hours.*.is_closed' => [
                'required',
                'boolean',
            ],

            'working_hours.*.opening_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'working_hours.*.closing_time' => [
                'nullable',
                'date_format:H:i',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $hours = $this->input('working_hours', []);

            $days = collect($hours)
                ->pluck('day_of_week');

            if ($days->unique()->count() !== 7) {
                $validator->errors()->add(
                    'working_hours',
                    'Each day of the week must appear exactly once.'
                );
            }

            foreach ($hours as $index => $hour) {
                if (
                    !($hour['is_closed'] ?? false)
                    && empty($hour['opening_time'])
                ) {
                    $validator->errors()->add(
                        "working_hours.$index.opening_time",
                        'Opening time is required when the clinic is open.'
                    );
                }

                if (
                    !($hour['is_closed'] ?? false)
                    && empty($hour['closing_time'])
                ) {
                    $validator->errors()->add(
                        "working_hours.$index.closing_time",
                        'Closing time is required when the clinic is open.'
                    );
                }
            }
        });
    }
}
