<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'appointment_id',
        'queue_id',
        'visit_date',
        'started_at',
        'completed_at',
        'status',
        'chief_complaint',
        'clinical_notes',
        'diagnosis',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function clinicalExtractions()
    {
        return $this->hasMany(ClinicalExtraction::class);
    }
    public function investigations()
    {
        return $this->hasMany(Investigation::class);
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }
}
