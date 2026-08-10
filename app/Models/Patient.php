<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'date_of_birth',
        'gender',
        'blood_group',
        'address',
        'weight',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
        ];
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function investigations()
    {
        return $this->hasMany(Investigation::class);
    }
}
