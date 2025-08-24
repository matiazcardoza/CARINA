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
        'final_balance',
        'class',         // nuevo
        'number',        // nuevo
        'observations',  // nuevo
    ];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    public function people()
    {
        return $this->belongsToMany(\App\Models\Person::class, 'movement_person', 'movement_kardex_id', 'person_dni')
            ->withPivot(['role','note','attached_at']);
    }
}
