<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementKardex extends Model
{
    use HasFactory;

    protected $table = 'movements_kardex';
    protected $primaryKey = 'id';

    protected $fillable = [
        'product_id',
        'movement_type',
        'movement_date',
        'amount',
        'final_balance'
    ];
}
