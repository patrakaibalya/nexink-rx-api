<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'date_of_birth',
        'gender',
        'blood_group',
        'address',
        'weight',
        'height',
        'is_diabetic',
        'diabetic_result',
        'blood_pressure',
        'blood_pressure_result',
        'uid_aadhar_no',
        'thyroid_result',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'is_diabetic' => 'boolean',
        ];
    }

    public function vitals()
    {
        return $this->hasMany(PatientVital::class)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id');
    }

    public function latestVital()
    {
        return $this->hasOne(PatientVital::class)
            ->latestOfMany('recorded_at');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function investigations()
    {
        return $this->hasMany(Investigation::class);
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }
}
