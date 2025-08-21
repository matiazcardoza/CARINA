<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'id_order_silucia',
        'id_product_silucia',
        'name',
        'heritage_code',
        'unit_price',
        'state',
        'category_id',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(MovementKardex::class, 'product_id');
    }
}
