<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureStep extends Model
{
    protected $fillable = [
        'report_id',
        'order',
        'role',
        'user_id',
        'page',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'status',
        'signed_at',
        'signed_by',
        'provider',
        'callback_token',
        'sha256',
    ];

    /* ========= Relaciones ========= */

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function assignee(): BelongsTo
    {
        // usuario esperado (cuando no es por rol)
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function signer(): BelongsTo
    {
        // usuario que realmente firmó
        return $this->belongsTo(\App\Models\User::class, 'signed_by');
    }
    
}
