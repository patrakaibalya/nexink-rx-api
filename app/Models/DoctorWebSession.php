<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorWebSession extends Model
{
    protected $fillable = [
        'doctor_id',
        'session_id',
        'revoked',
    ];

    protected function casts(): array
    {
        return [
            'revoked' => 'boolean',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            DoctorAccount::class,
            'doctor_id'
        );
    }
}
