<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelOrder extends Model
{
        protected $fillable = [
        'fecha',
        'numero',
        'orden_compra',
        'componente',
        'grifo',
        'driver_id',
        'vehicle_id',
        'vehiculo_marca',
        'vehiculo_placa',
        'vehiculo_dependencia',
        'hoja_viaje',
        'motivo',
        'fuel_type',
        'quantity_gal',
        'amount_soles',
        'supervisor_status',
        'supervisor_id',
        'supervisor_at',
        'supervisor_note',
        'manager_status',
        'manager_id',
        'manager_at',
        'manager_note',
    ];

    protected $casts = [
        'fecha' => 'date',
        'quantity_gal' => 'decimal:2',
        'amount_soles' => 'decimal:2',
        'supervisor_at' => 'datetime',
        'manager_at' => 'datetime',
    ];

    public function driver(): BelongsTo { return $this->belongsTo(User::class, 'driver_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function supervisor(): BelongsTo { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'manager_id'); }

    // Estado global calculado (opcional)
    protected $appends = ['status_global'];

    public function getStatusGlobalAttribute(): string
    {
        if ($this->supervisor_status === 'rejected' || $this->manager_status === 'rejected') {
            return 'rejected';
        }
        if ($this->supervisor_status === 'approved' && $this->manager_status === 'approved') {
            return 'approved';
        }
        return 'pending';
    }
}
