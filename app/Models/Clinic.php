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
        'consultation_fee',
        'active_pricing_organization_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'appointment_duration_minutes' => 'integer',
            'consultation_fee' => 'decimal:2',
        ];
    }

    public function workingHours()
    {
        return $this->hasMany(ClinicWorkingHour::class);
    }

    public function activePricingOrganization()
    {
        return $this->belongsTo(
            MedicineOrganization::class,
            'active_pricing_organization_id'
        );
    }
}
