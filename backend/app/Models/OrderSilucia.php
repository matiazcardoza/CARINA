<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderSilucia extends Model
{
    use HasFactory;
    protected $table = 'orders_silucia';
    protected $primaryKey = 'id';

    protected $fillable = [
        'silucia_id',
        'order_type',
        'issue_date',
        'goal_project',
        'goal_detail',
        'api_date',
        'state'
    ];
    // ----------------
    protected $casts = [
        'issue_date' => 'date',
        'state'      => 'integer',
        // 'api_date' => 'datetime', // tu migración la define como string; si cambias a datetime, activa este cast
    ];

    /** Relaciones */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'order_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'order_id');
    }

    /** Scopes útiles */
    // public function scopeActive($query)
    // {
    //     return $query->where('state', 1);
    // }
}
