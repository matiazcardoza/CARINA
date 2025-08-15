<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
