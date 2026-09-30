<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One recording of a patient's vitals, usually taken at a visit by the
 * doctor or reception staff. Readings are free text.
 */
class PatientVital extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    /**
     * The reading fields (everything a recording can hold besides links and metadata).
     */
    public const READING_FIELDS = [
        'weight',
        'height',
        'blood_pressure_result',
        'pulse',
        'temperature',
        'spo2',
        'blood_sugar',
        'diabetic_result',
        'thyroid_result',
    ];

    protected $fillable = [
        'patient_id',
        'visit_id',
        'clinic_id',
        'weight',
        'height',
        'blood_pressure_result',
        'pulse',
        'temperature',
        'spo2',
        'blood_sugar',
        'diabetic_result',
        'thyroid_result',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
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
