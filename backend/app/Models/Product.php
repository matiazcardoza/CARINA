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
        'in_qty','out_qty','stock_qty','last_movement_at',
    ];

    protected $casts = [
        'id_order_silucia'   => 'string',
        'id_product_silucia' => 'integer',
        'in_qty'     => 'decimal:4',
        'out_qty'    => 'decimal:4',
        'stock_qty'  => 'decimal:4',
        'unit_price' => 'decimal:2',
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

    public function recalcCounters(): void
    {
        $in  = $this->movements()->where('movement_type','entrada')->sum('amount');
        $out = $this->movements()->where('movement_type','salida')->sum('amount');
        $this->forceFill([
            'in_qty'    => $in,
            'out_qty'   => $out,
            'stock_qty' => $in - $out,
        ])->save();
    }
}
