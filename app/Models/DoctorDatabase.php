<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'status',
        'provisioned_at',
    ];

    protected $hidden = [
        'database_password',
    ];

    protected function casts(): array
    {
        return [
            'database_port' => 'integer',
            'database_password' => 'encrypted',
            'provisioned_at' => 'datetime',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorAccount::class, 'doctor_id');
    }
}
