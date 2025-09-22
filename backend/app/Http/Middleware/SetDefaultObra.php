<?php

namespace App\Http\Middleware;

use App\Models\Obra;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDefaultObra
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $obraId = 6;
        $obra = Obra::findOrFail($obraId );
        setPermissionsTeamId($obra->id);  // <- API oficial v6
        // También expón para que tu código pueda leerlo si quiere:
        app()->instance('currentObraId', $obra->id);
        return $next($request);
    }
}
