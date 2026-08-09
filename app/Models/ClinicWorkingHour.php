<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicWorkingHour extends Model
{
    protected $connection = 'doctor';

    protected $fillable = [
        'clinic_id',
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
        ];
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
