<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'people';
    protected $primaryKey = 'dni';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'dni','first_lastname','second_lastname','names','full_name',
        'civil_status','address','ubigeo','ubg_department','ubg_province',
        'ubg_district','photo_base64','reniec_consulted_at'
    ];
        protected $casts = [
        // 'raw' => 'array',
        'reniec_consulted_at' => 'datetime',
    ];

    public function movements()
    {
        return $this->belongsToMany(MovementKardex::class, 'movement_person', 'person_dni', 'movement_kardex_id')
            ->withPivot(['role','note','attached_at']);
    }

}
