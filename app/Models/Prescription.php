<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'visit_id',
        'prescription_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'prescription_date' => 'date',
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

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class)
            ->orderBy('sort_order');
    }
}
