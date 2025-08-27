<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignatureStep extends Model
{
    protected $fillable = [
        'signature_flow_id','order','role','user_id',
        'page','pos_x','pos_y','width','height',
        'status','signed_at','signed_by','provider','provider_tx_id',
        'certificate_cn','certificate_serial','callback_token','signed_pdf_path'
    ];
    public function flow(){ return $this->belongsTo(SignatureFlow::class); }
}
