<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcedureItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'doctor';

    protected $fillable = [
        'procedure_id',
        'procedure_name',
        'instructions',
        'outcome_notes',
        'sort_order',
    ];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}
