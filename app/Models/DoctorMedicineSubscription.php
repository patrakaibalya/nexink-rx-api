<?php

namespace App\Models;

use App\Models\DoctorAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorMedicineSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'organization_id',
        'status',
        'is_favorite',
        'requested_at',
        'approved_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorAccount::class, 'doctor_id');
    }

    public function organization()
    {
        return $this->belongsTo(
            MedicineOrganization::class,
            'organization_id'
        );
    }
}
