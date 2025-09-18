<?php

namespace App\Http\Controllers;

use App\Models\ItemPecosa;
use App\Services\PecosaClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\RequestException;

class PecosaController extends Controller
{
    public function __construct(private PecosaClient $silucia) {}
    public function index(Request $request){

        // (Opcional) valida parámetros que aceptarás como query
        $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'numero'   => 'nullable|string|max:100',
            'anio'     => 'nullable|integer|digits:4',
            'item'     => 'nullable|string|max:255',
            'desmeta'  => 'nullable|string|max:255',
            'siaf'     => 'nullable|string|max:50',
            'ruc'      => 'nullable|string|max:20',
            'rsocial'  => 'nullable|string|max:255',
            'email'    => 'nullable|string|max:255',
        ]);

        try {
            // Llama a tu cliente tal cual lo tienes
            $data = $this->silucia->index($request);
            return response()->json($data, 200);
        } catch (RequestException $e) {
            // Propaga un error manejable al frontend
            $status = $e->response?->status() ?? 502;
            return response()->json([
                'message' => 'Error al consultar Silucia - pecosas',
                'error'   => $e->getMessage(),
            ], $status);
        }
    }

    public function getPecosasByWorksx(Request $request){
        $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'numero'   => 'nullable|string|max:100',
            'anio'     => 'nullable|integer|digits:4',
            'item'     => 'nullable|string|max:255',
            'desmeta'  => 'nullable|string|max:255',
            'siaf'     => 'nullable|string|max:50',
            'ruc'      => 'nullable|string|max:20',
            'rsocial'  => 'nullable|string|max:255',
            'email'    => 'nullable|string|max:255',
        ]);
        $pecosas = ItemPecosa::query()
            ->select(                                // FKs internas
                'id',
                'obra_id',
                'orden_id',

                // Identificadores Silucia
                'idsalidadet_silucia',   // único
                'idcompradet_silucia',   // opcional

                // Búsquedas típicas
                'anio',
                'numero',

                // Datos de pecosa
                'fecha',
                'prod_proy',
                'cod_meta',
                'desmeta',
                'desuoper',
                'destipodestino',
                'item',
                'desmedida',

                // Detalle numérico
                'cantidad',
                'precio',
                'saldo',
                'total',

                // Referencia cruzada
                'numero_origen',

                // Metadatos de sincronización
                'external_last_seen_at',
                'external_hash',
                'raw_snapshot',
                )
            ->orderByDesc('fecha')
            // ->paginate(20);
            ->get();

        return response()->json($pecosas);
    }

    public function getPecosasByWorks(Request $request)
    {
        $validated = $request->validate([
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'numero'   => 'nullable|string|max:100',
            'anio'     => 'nullable|integer|digits:4',
            'item'     => 'nullable|string|max:255',
            'desmeta'  => 'nullable|string|max:255',
            'siaf'     => 'nullable|string|max:50',
            'ruc'      => 'nullable|string|max:20',
            'rsocial'  => 'nullable|string|max:255',
            'email'    => 'nullable|string|max:255', // si quieres validar correo: 'email'
            'obra_id'  => 'nullable|integer|min:1',  // opcional: filtrar por obra específica
            'include_raw' => 'nullable|boolean',     // opcional: incluir raw_snapshot
        ]);

        $page     = $validated['page']     ?? 1;
        $perPage  = $validated['per_page'] ?? 20;
        $includeRaw = (bool)($validated['include_raw'] ?? false);

        $columns = [
            'id','obra_id','orden_id',
            'idsalidadet_silucia','idcompradet_silucia',
            'anio','numero','fecha','prod_proy','cod_meta','desmeta','desuoper','destipodestino',
            'item','desmedida','cantidad','precio','saldo','total','numero_origen',
            'external_last_seen_at','external_hash',
        ];
        if ($includeRaw) {
            $columns[] = 'raw_snapshot';
        }

        $q = ItemPecosa::query()->select($columns);

        // Filtros condicionales (solo si llegan)
        $q->when($request->filled('obra_id'), fn($qq) => $qq->where('obra_id', $request->obra_id));
        $q->when($request->filled('anio'),    fn($qq) => $qq->where('anio', $request->anio));
        $q->when($request->filled('numero'),  fn($qq) => $qq->where('numero','like','%'.$request->numero.'%'));
        $q->when($request->filled('item'),    fn($qq) => $qq->where('item','like','%'.$request->item.'%'));
        $q->when($request->filled('desmeta'), fn($qq) => $qq->where('desmeta','like','%'.$request->desmeta.'%'));

        // NOTA: ruc/rsocial/email/siaf requieren join/relación si no están en item_pecosas.
        // Ejemplo (si existe relación orden -> proveedor):
        // $q->when($request->filled('ruc'), fn($qq) => $qq->whereHas('orden.proveedor', fn($w) => $w->where('ruc',$request->ruc)));

        // Orden: nulos al final (MySQL/MariaDB)
        $q->orderByRaw('fecha IS NULL, fecha DESC');

        $pecosas = $q->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Pecosas retrieved successfully',
            'data'    => $pecosas->items(),
            'meta'    => [
                'current_page' => $pecosas->currentPage(),
                'per_page'     => $pecosas->perPage(),
                'total'        => $pecosas->total(),
                'last_page'    => $pecosas->lastPage(),
            ],
        ]);
    }

}
