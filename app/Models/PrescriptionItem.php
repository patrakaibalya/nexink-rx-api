<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrescriptionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'prescription_id',
        'medicine_name',
        'dosage',
        'frequency',
        'duration',
        'route',
        'instructions',
        'sort_order',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
