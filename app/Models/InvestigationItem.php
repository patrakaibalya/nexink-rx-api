<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestigationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'investigation_id',
        'test_name',
        'test_type',
        'instructions',
        'sort_order',
    ];

    public function investigation()
    {
        return $this->belongsTo(Investigation::class);
    }
}
