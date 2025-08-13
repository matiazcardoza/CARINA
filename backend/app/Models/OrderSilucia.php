<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSilucia extends Model
{
    use HasFactory;
    protected $table = 'orders_silucia';
    protected $primaryKey = 'id';

    protected $fillable = [
        'silucia_id',
        'order_type',
        'issue_date',
        'goal_project',
        'state',
    ];
}
