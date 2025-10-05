<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenCompraDetallado extends Model
{
    protected $table = 'ordenes_compra_detallado';

    protected $fillable = [
        'obra_id',
        'orden_id',
        'idcompradet',
        'anio',
        'numero',
        'siaf',
        'prod_proy',
        'fecha',
        'fecha_aceptacion',
        'item',
        'desmedida',
        'cantidad',
        'precio',
        'saldo',
        'total_internado',
        'internado',
        'idmeta',
        'quantity_received',
        'quantity_issued',
        'quantity_on_hand',
        'external_last_seen_at',
        'external_hash',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_aceptacion' => 'date',
        'cantidad' => 'integer',
        'saldo' => 'integer',
        'precio' => 'decimal:2',
        'total_internado' => 'decimal:2',
        'quantity_received' => 'decimal:3',
        'quantity_issued' => 'decimal:3',
        'quantity_on_hand' => 'decimal:3',
        'external_last_seen_at' => 'datetime',
    ];

    // Relación: pertenece a una obra
    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    // Relación: pertenece a una orden de compra (opcional)
    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

}
