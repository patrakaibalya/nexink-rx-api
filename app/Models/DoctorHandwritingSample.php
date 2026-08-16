<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorHandwritingSample extends Model
{
    use HasFactory;

    protected $connection = 'doctor';

    protected $table = 'doctor_handwriting_samples';

    protected $fillable = [
        'doctor_id',
        'sample_type',
        'prescription_id',
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
