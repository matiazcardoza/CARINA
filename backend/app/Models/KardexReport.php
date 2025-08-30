<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexReport extends Model
{
    protected $fillable = ['product_id','pdf_path','pdf_page_number','latest_pdf_path','from_date','to_date','type','status','created_by'];
    public function product(){ return $this->belongsTo(Product::class); }
    public function flow(){ return $this->hasOne(SignatureFlow::class); }
}
