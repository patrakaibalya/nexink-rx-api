<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicPrescriptionTemplate extends Model
{
    use HasFactory;

    protected $connection = 'doctor';

    protected $fillable = [
        'clinic_id',
        'page_width',
        'page_height',
        'header_image_path',
        'header_width',
        'header_height',
        'footer_image_path',
        'footer_width',
        'footer_height',
    ];

    protected function casts(): array
    {
        return [
            'page_width' => 'integer',
            'page_height' => 'integer',
            'header_width' => 'integer',
            'header_height' => 'integer',
            'footer_width' => 'integer',
            'footer_height' => 'integer',
        ];
    }
}
