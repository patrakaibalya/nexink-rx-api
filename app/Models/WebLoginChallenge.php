<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebLoginChallenge extends Model
{
    protected $fillable = [
        'challenge',
        'doctor_id',
        'status',
        'expires_at',
        'approved_at',
        'consumed_at',
        'handoff_hash',
        'handoff_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'consumed_at' => 'datetime',
            'handoff_expires_at' => 'datetime',
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
