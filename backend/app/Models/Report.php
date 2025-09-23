<?php

namespace App\Models;

use App\Traits\BelongsToObra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    /**
     * este valor se usa para filtrar por obra, si es que 
     * la tabla cuenta con una columna obra_id
     */
    // use BelongsToObra;

    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'pdf_path',
        'pdf_page_number',
        'status',
        'current_step',
        'generation_params',
        'signing_starts_at',
        'signing_ends_at',
        'created_by',        
    ];

    /* ========= Relaciones ========= */
    /**
     * sirve para obtner el dueño del reporte y como report funciona para ditintos
     * modelos entonces la relacion se configura de esta manera
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function steps(): HasMany
    {
        // OJO: permite paralelos con el mismo order; por defecto ordenamos por order ASC, id ASC
        return $this->hasMany(SignatureStep::class)->orderBy('order')->orderBy('id');
    }

    public function currentStep()
    {
        return $this->hasOne(SignatureStep::class)
            ->whereColumn('signature_steps.order', 'reports.current_step');
    }
}
