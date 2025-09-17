<?php

namespace App\Http\Controllers;

// namespace App\Http\Controllers\Admin;

// use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Services\MetaClient;
use Illuminate\Http\Client\RequestException;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class AdminCatalogController extends Controller
{
    public function __construct(private MetaClient $meta) {}
    public function obras()
    {
        return Obra::query()->orderBy('nombre')->get(['id','nombre','codigo']);
    }

    public function roles()
    {
        // roles definidos (Spatie). Si usas guards distintos, filtra por guard_name
        return Role::query()->orderBy('name')->get(['id','name','guard_name']);
    }

    public function obrasBySiluciaTest(Request $request){
        // 'page', 'per_page', 'idmeta','anio', 'codmeta','codmeta'
        $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'idmeta'   => 'nullable|string|max:100',
            'anio'     => 'nullable|string|max:100',
            'codmeta'     => 'nullable|string|max:255',
            // 'anio'     => 'nullable|integer|digits:4',
            // 'desmeta'  => 'nullable|string|max:255',
            // 'siaf'     => 'nullable|string|max:50',
            // 'ruc'      => 'nullable|string|max:20',
            // 'rsocial'  => 'nullable|string|max:255',
            // 'email'    => 'nullable|string|max:255',
        ]);

        try{
            
            $data = $this->meta->index($request);
            return response()->json($data, 200);
        } catch(RequestException $e){
            $status = $e->response?->status() ?? 502;
            return response()->json([
                'message' => 'Error al consultar Silucia - pecosas',
                'error'   => $e->getMessage(),
            ], $status);
        }
    }
}
