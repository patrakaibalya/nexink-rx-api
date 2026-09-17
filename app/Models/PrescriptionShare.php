<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'organization_id',
        'clinical_extraction_id',
        'visit_id',
        'patient_id',
        'prescription_id',
        'patient_snapshot',
        'clinic_snapshot',
        'payload',
        'has_handwriting_sample',
        'status',
        'shared_at',
        'viewed_at',
        'dispensed_at',
    ];

    protected function casts(): array
    {
        return [
            'patient_snapshot' => 'array',
            'clinic_snapshot' => 'array',
            'payload' => 'array',
            'has_handwriting_sample' => 'boolean',
            'shared_at' => 'datetime',
            'viewed_at' => 'datetime',
            'dispensed_at' => 'datetime',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(DoctorAccount::class, 'doctor_id');
    }

    public function organization()
    {
        return $this->belongsTo(MedicineOrganization::class, 'organization_id');
    }
}
