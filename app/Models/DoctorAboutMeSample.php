<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorAboutMeSample extends Model
{
    use HasFactory;

    protected $connection = 'doctor';

    protected $table = 'doctor_about_me';

    protected $fillable = [
        'doctor_id',
        'tool_data',
        'raw_recognized_text',
        'final_corrected_text',
        'ink_file_path',
        'qdrant_status',
    ];

    protected function casts(): array
    {
        return [
            'tool_data' => 'array',
        ];
    }
}
