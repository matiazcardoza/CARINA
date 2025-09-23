<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignatureFlow extends Model
{
    protected $fillable = [ 'report_id','current_step','status'];
    
    // public function report(){ 
    //     return $this->belongsTo(KardexReport::class, 'kardex_report_id'); 
    // }
    public function report() {
         return $this->belongsTo(Report::class); 
    }
    public function steps()  { 
        return $this->hasMany(SignatureStep::class)->orderBy('order'); 
    }
    // public function currentStep(){
    //     return $this->hasOne(SignatureStep::class)->whereColumn('order','current_step');
        
    // }

    // public function currentStep()
    // {
    //     return $this->hasOne(SignatureStep::class)
    //         ->whereColumn('signature_steps.order', 'signature_flows.current_step');
    // }


    


    public function currentStep()
    {
        return $this->hasOne(SignatureStep::class, 'signature_flow_id')
            ->where('order', $this->current_step);
    }
}
