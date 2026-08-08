<?php

namespace App\Models;

use App\Models\DoctorDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class DoctorAccount extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'specialization',
        'medical_license_number',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function database()
    {
        return $this->hasOne(DoctorDatabase::class, 'doctor_id');
    }

    public function medicineSubscriptions()
    {
        return $this->hasMany(
            DoctorMedicineSubscription::class,
            'doctor_id'
        );
    }
}
