<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestigationDocument extends Model
{
    use HasFactory;

    protected $connection = 'doctor';

    protected $fillable = [
        'investigation_id',
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

    public function investigation()
    {
        return $this->belongsTo(Investigation::class);
    }
}
