<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyPart extends Model
{
    use HasFactory;

    protected $table = 'daily_parts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'service_id',
        'description',
        'work_date',
        'start_time',
        'end_time',
        'initial_fuel',
        'final_fuel',
        'time_worked',
        'fuel_consumed',
        'state',
    ];
}
