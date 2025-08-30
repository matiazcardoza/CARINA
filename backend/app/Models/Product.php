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
        'numero','fecha','detalles_orden','rsocial','ruc','item','detalle',
        'cantidad','desmedida','precio','total_internado','saldo','pdf_filename','desmeta',
    ];

    protected $casts = [
        'id_order_silucia'   => 'string',
        'id_product_silucia' => 'integer',
    ];


    public function movements(): HasMany
    {
        return $this->hasMany(MovementKardex::class, 'product_id');
    }

    public function kardexReports(): HasMany
    {
        return $this->hasMany(KardexReport::class, 'product_id');
    }
    
    public function reports() {
        return $this->hasMany(\App\Models\KardexReport::class, 'product_id');
    }
}
