<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorManualCorrection extends Model
{
    protected $connection = 'doctor';

    protected $table = 'doctor_manual_corrections';

    protected $fillable = [
        'doctor_id',
        'wrong_word',
        'correct_word',
        'category',
    ];
}
