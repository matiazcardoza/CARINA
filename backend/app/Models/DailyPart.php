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
        'itemPecosa_id',
        'document_id',
        'movement_kardex_id',
        'shift_id',
        'operator_id',
        'description',
        'occurrences',
        'num_reg',
        'work_date',
        'start_time',
        'end_time',
        'initial_fuel',
        'gasolina',
        'time_worked',
        'state_valorized',
        'state',
    ];

    public function document()
    {
        return $this->belongsTo(DocumentDailyPart::class, 'document_id');
    }
}
