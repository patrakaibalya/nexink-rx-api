<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalInvestigationLibrary extends Model
{
    use HasFactory;

    protected $table = 'global_investigation_library';

    protected $fillable = [
        'investigation_name',
        'description',
        'purchase_price',
        'sale_price',
        'unit',
        'unit_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'unit' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
