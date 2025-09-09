<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MechanicalEquipment extends Model
{
    use HasFactory;

    protected $table = 'mechanical_equipment';
    protected $primaryKey = 'id';

    protected $fillable = [
        'machinery_equipment',
        'ability',
        'brand',
        'model',
        'serial_number',
        'year',
        'plate',
        'cost_hour',
        'state'
    ];
}
