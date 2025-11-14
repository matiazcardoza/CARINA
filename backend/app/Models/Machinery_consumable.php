<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class Machinery_consumable extends Model
{
    use HasFactory;
    
    protected $table = 'machinery_consumables';
    protected $primaryKey = 'id';
    protected $fillable = [
        'daily_part_id',
        'name',
        'unit_measure',
    ];
}
