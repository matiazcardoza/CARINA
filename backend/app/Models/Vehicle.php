<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'plate',
        'dependencia',
        'user_id',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
