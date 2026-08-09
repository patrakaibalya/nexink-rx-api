<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'pincode',
        'mobile',
        'email',
        'timezone',
        'is_active',
        'appointment_duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'appointment_duration_minutes' => 'integer',
        ];
    }

    public function workingHours()
    {
        return $this->hasMany(ClinicWorkingHour::class);
    }
}
