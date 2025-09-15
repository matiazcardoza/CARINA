<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\DB;

use App\Models\Obra;

class ResolveCurrentObra
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $obraId = $request->header('X-Obra-Id') ?? $request->route('obra');
        abort_if(!$obraId, 400, 'Falta X-Obra-Id');

        $obra = Obra::findOrFail($obraId);

        $isMember = DB::table('obra_user')->where([
        'obra_id' => $obra->id,
        'user_id' => $request->user()->id,
        ])->exists();
        abort_if(!$isMember, 403, 'No perteneces a esta obra');

        // Fijar el TEAM (obra) ACTIVO para Spatie:
        setPermissionsTeamId($obra->id);  // <- API oficial v6
        // También expón para que tu código pueda leerlo si quiere:
        app()->instance('currentObraId', $obra->id);
        return $next($request);
    }
}
