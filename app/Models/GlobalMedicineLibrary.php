<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalMedicineLibrary extends Model
{
    use HasFactory;

    protected $table = 'global_medicine_library';

    protected $fillable = [
        'medicine_name',
        'generic_name',
        'composition',
        'strength',
        'dosage_form',
        'manufacturer',
        'description',
        'purchase_price',
        'sale_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
