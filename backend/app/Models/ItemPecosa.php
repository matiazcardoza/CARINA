<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPecosa extends Model
{
    use HasFactory;

    protected $table = 'item_pecosas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * Asignación masiva segura
     */
    protected $fillable = [
        // Referencias Silucia
        'id_container_silucia',
        'id_item_pecosa_silucia',

        // Datos administrativos / logísticos
        'anio',
        'numero',
        'fecha',
        'prod_proy',
        'cod_meta',
        'desmeta',
        'desuoper',
        'destipodestino',

        // Detalle del ítem
        'item',
        'desmedida',
        'idsalidadet',
        'cantidad',
        'precio',
        'tipo',
        'saldo',
        'total',
        'numero_origen',
    ];

    /**
     * Casts para tipos nativos
     */
    protected $casts = [
        'anio'                  => 'integer',
        'fecha'                 => 'date',
        'id_container_silucia'  => 'integer',
        'id_item_pecosa_silucia'=> 'integer',
        'idsalidadet'           => 'integer',
        'cantidad'              => 'decimal:2',
        'precio'                => 'decimal:2',
        'saldo'                 => 'decimal:2',
        'total'                 => 'decimal:2',
    ];

    public function movements()
    {
        return $this->hasMany(MovementKardex::class, 'item_pecosa_id');
    }


    /* ================================
     * Scopes de ayuda para filtrar
     * ================================ */

    public function scopeNumero($query, ?string $numero)
    {
        return $numero ? $query->where('numero', $numero) : $query;
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
