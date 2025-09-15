<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Obra extends Model
{
    use HasFactory;

    protected $fillable = ['nombre','codigo'];

    // Users miembros (pivot obra_user)
    public function members()
    {
        return $this->belongsToMany(User::class, 'obra_user')->withTimestamps();
    }

    // public function warehouses()       { return $this->hasMany(Warehouse::class); }
    // public function ordenesCompra()    { return $this->hasMany(OrdenCompra::class); }
    public function itemPecosas()      { return $this->hasMany(ItemPecosa::class); }
    public function movementsKardex()  { return $this->hasMany(MovementKardex::class); }
}
