<?php

// Nota: la API oficial de teams usa helpers setPermissionsTeamId(...) / getPermissionsTeamId() para fijar y leer el team_id activo del request
namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;
trait BelongsToObra
{
    protected static function bootBelongsToObra() {
        static::creating(function ($model) {
            if (function_exists('getPermissionsTeamId')) {
                $obraId = getPermissionsTeamId();
                if ($obraId && empty($model->obra_id)) $model->obra_id = $obraId;
            }
        });
        static::addGlobalScope('obra', function (Builder $q) {
            if (function_exists('getPermissionsTeamId')) {
                $obraId = getPermissionsTeamId();
                if ($obraId) $q->where($q->getModel()->getTable().'.obra_id', $obraId);
            }
        });
    }
}
