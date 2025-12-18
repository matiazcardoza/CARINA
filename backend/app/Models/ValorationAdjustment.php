<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValorationAdjustment extends Model
{
    use HasFactory;
    protected $table = 'valoration_adjustment';
    protected $primaryKey = 'id';
    protected $fillable = [
        'goal_id',
        'adjusted_data',
        'deductive_order',
        'deductive_sheet',
        'num_reg',
        'updated_by',
    ];
}
