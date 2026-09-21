<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedureDocument extends Model
{
    use HasFactory;

    protected $connection = 'doctor';

    protected $fillable = [
        'procedure_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}
