<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignatureEvent extends Model
{
    protected $fillable = ['signature_flow_id','signature_step_id','user_id','event','meta'];
    protected $casts   = ['meta'=>'array'];
}
