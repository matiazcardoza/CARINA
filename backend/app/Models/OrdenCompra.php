<?php

namespace App\Models;

use App\Traits\BelongsToObra;
use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    use BelongsToObra;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'obra_id','ext_order_id','fecha','proveedor','monto_total',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'monto_total' => 'decimal:2',
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function items()
    {
        return $this->hasMany(ItemPecosa::class, 'orden_id');
    }
}
