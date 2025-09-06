<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'mechanical_equipment_id',
        'goal_id',
        'operator',
        'description',
        'goal_project',
        'goal_detail',
        'fuel_consumed',
        'state',
        'state_closure'
    ];
}
