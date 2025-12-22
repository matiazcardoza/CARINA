<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLiquidationAdjustment extends Model
{
    use HasFactory;
    
    protected $table = 'service_liquidation_adjustments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'service_id',
        'adjusted_data',
        'num_reg',
        'updated_by',
        'state_valorized'
    ];
}
