<?php

namespace App\Models;

use App\Traits\BelongsToObra;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPecosa extends Model
{
    use HasFactory;
    use BelongsToObra;
    protected $table = 'item_pecosas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * Asignación masiva segura
     */
    protected $fillable = [
        // FKs internas
        'id',
        'obra_id',
        'orden_id',

        // Identificadores Silucia
        'idsalidadet_silucia',   // único
        'idcompradet_silucia',   // opcional

        // Búsquedas típicas
        'anio',
        'numero',

        // Datos de pecosa
        'fecha',
        'prod_proy',
        'cod_meta',
        'desmeta',
        'desuoper',
        'destipodestino',
        'item',
        'desmedida',

        // Detalle numérico
        'cantidad',
        'precio',
        'saldo',
        'total',

        // Referencia cruzada
        'numero_origen',

        // Metadatos de sincronización
        'external_last_seen_at',
        'external_hash',
        'raw_snapshot',
    ];

    /**
     * Casts para tipos nativos
     */
    protected $casts = [
        'fecha'                 => 'date',
        'cantidad'              => 'integer',      // unsigned en DB
        'precio'                => 'decimal:2',
        'saldo'                 => 'integer',
        'total'                 => 'decimal:2',
        'external_last_seen_at' => 'datetime',
        'raw_snapshot'          => 'array',        // longText con JSON
    ];

    public function obra()
    {
        return $this->belongsTo(Obra::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function movements()
    {
        return $this->hasMany(MovementKardex::class, 'item_pecosa_id');
    }


    /* ================================
     * Scopes de ayuda para filtrar
     * ================================ */

    public function scopeNumero($query, ?string $numero)
    {
        return $numero ? $query->where('id_pecosa_silucia', $numero) : $query;
    }

    public function scopeAnio($query, $anio)
    {
        return filled($anio) ? $query->where('anio', (int) $anio) : $query;
    }

    /**
     * Búsqueda por texto en campos comunes
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        $t = '%' . trim($term) . '%';
        return $query->where(function ($q) use ($t) {
            $q->where('item', 'like', $t)
              ->orWhere('desmeta', 'like', $t)
              ->orWhere('desuoper', 'like', $t)
              ->orWhere('destipodestino', 'like', $t)
              ->orWhere('desmedida', 'like', $t)
              ->orWhere('prod_proy', 'like', $t)
              ->orWhere('cod_meta', 'like', $t);
        });
    }

    /**
     * Filtro compuesto según tu UI (solo campos existentes en la tabla)
     *
     * Uso: ItemPecosa::filter($filters)->paginate(...)
     * $filters = ['numero' => '...', 'anio' => 2025, 'item' => '...', 'desmeta' => '...']
     */
    public function scopeFilter($query, array $filters = [])
    {
        return $query
            ->numero($filters['numero'] ?? null)
            ->anio($filters['anio'] ?? null)
            ->when(isset($filters['item']) && $filters['item'] !== '', function ($q) use ($filters) {
                $q->where('item', 'like', '%' . $filters['item'] . '%');
            })
            ->when(isset($filters['desmeta']) && $filters['desmeta'] !== '', function ($q) use ($filters) {
                $q->where('desmeta', 'like', '%' . $filters['desmeta'] . '%');
            });
    }

    // public function reports()
    // {
    //     return $this->morphMany(\App\Models\KardexReport::class, 'reportable');
    // }
    public function reports() { 
        return $this->morphMany(Report::class, 'reportable'); 
    }


    /* ================================
     * Relaciones (si las necesitas)
     * ================================ */

    // Ejemplos (descomentar y ajustar cuando existan estas tablas/modelos):
    // public function movements()
    // {
    //     return $this->hasMany(MovementKardex::class, 'item_pecosa_id'); // clave foránea en movements
    // }

    // public function container()
    // {
    //     return $this->belongsTo(SiluciaContainer::class, 'id_container_silucia', 'id_externo');
    // }
}
