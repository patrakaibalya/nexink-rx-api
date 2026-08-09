<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalExtraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        'schema_version',
        'status',
        'confidence',
        'payload',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
            'payload' => 'array',
            'confirmed_at' => 'datetime',
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

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
