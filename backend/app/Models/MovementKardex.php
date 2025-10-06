<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MovementKardex extends Model
{
    use HasFactory;

    protected $table = 'movements_kardex';
    protected $primaryKey = 'id';

    protected $fillable = [
        // 'item_pecosa_id',
        'ordenes_compra_detallado_id',
        'created_by',
        'product_id',
        'movement_type',
        'movement_date',
        'amount',
        'final_balance',
        'class',         // nuevo
        'number',        // nuevo
        'observations',  // nuevo
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount'        => 'decimal:2',
    ];

    public function itemPecosa()
    {
        return $this->belongsTo(ItemPecosa::class, 'item_pecosa_id');
    }
    // public function product(): BelongsTo
    // {
    //     return $this->belongsTo(Product::class, 'product_id');
    // }
    
    // public function people()
    // {
    //     return $this->belongsToMany(\App\Models\Person::class, 'movement_person', 'movement_kardex_id', 'person_dni')
    //         ->withPivot(['role','note','attached_at']);
    // }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,           // modelo relacionado
            'movement_user',       // tabla pivote
            'movement_kardex_id',  // FK local en pivote
            'user_id'              // FK del relacionado en pivote
        )->withPivot(['attached_at']);
    }

    // con esto obtendremos al autor que creó el movimiento
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
