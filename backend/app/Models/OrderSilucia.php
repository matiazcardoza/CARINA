<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderSilucia extends Model
{
    use HasFactory;
    protected $table = 'orders_silucia';
    protected $primaryKey = 'id';

    protected $fillable = [
        'silucia_id',
        'order_type',
        'supplier',
        'ruc_supplier',
        'delivery_date',
        'deadline_day',
        'state',
    ];
}