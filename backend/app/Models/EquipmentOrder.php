<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentOrder extends Model
{
    use HasFactory;

    protected $table = 'equipment_order';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_silucia_id',
        'machinery_equipment',
        'ability',
        'brand',
        'model',
        'serial_number',
        'year',
        'plate',
    ];
}
