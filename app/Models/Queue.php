<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $connection = 'doctor';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'queue_number',
        'queue_date',
        'source',
        'status',
        'arrived_at',
        'called_at',
        'consultation_started_at',
        'completed_at',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
            'arrived_at' => 'datetime',
            'called_at' => 'datetime',
            'consultation_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
