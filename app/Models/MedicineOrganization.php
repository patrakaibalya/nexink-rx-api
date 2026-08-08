<?php

namespace App\Models;

use App\Models\DoctorMedicineSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class MedicineOrganization extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'organization_name',
        'email',
        'mobile',
        'contact_person',
        'address',
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

    public function subscriptions()
    {
        return $this->hasMany(DoctorMedicineSubscription::class, 'organization_id');
    }

    public function getMedicineLibraryTable(): string
    {
        return 'medicine_library_' . $this->id;
    }
}
