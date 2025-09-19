<?php

namespace App\Http\Controllers;

use App\Models\ItemPecosa;
use App\Models\Obra;
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

    public function testPecosasV0(Request $request, Obra $obra){
        // Filtros básicos
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:200',
            'anio' => 'nullable|integer',
            'numero' => 'nullable|string|max:50',
        ]);

        $q = ItemPecosa::query()->where('obra_id', $obra->id);

        if ($request->filled('anio'))   $q->where('anio', $request->integer('anio'));
        if ($request->filled('numero')) $q->where('numero', 'like', '%'.$request->get('numero').'%');

        $perPage = (int) ($request->get('per_page', 20));
        $page = (int) ($request->get('page', 1));

        $p = $q->orderByDesc('fecha')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $p->items(),
            'total' => $p->total(),
            'per_page' => $p->perPage(),
        ]);
    }
    public function testPecosas(Request $request, Obra $obra){
        // Filtros básicos

        $user  = $request->user();
        // return $user;
        $roles = $user->getRoleNames()->toArray();
        $isOperator = $user->hasRole('almacen.operador');

        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:200',
            'anio' => 'nullable|integer',
            'numero' => 'nullable|string|max:50',
        ]);

        // Base: por obra
        $q = ItemPecosa::query()->where('obra_id', $obra->id);

        // Regla de visibilidad por Rol:
        // - operador => TODOS
        // - admin/residente/supervisor => SOLO con reportes
        if (!$isOperator) {
            $q->whereHas('reports'); // al menos 1 reporte
        }

        // filtros UI (opcionales)
        if ($request->filled('anio'))   $q->where('anio', $request->integer('anio'));
        if ($request->filled('numero')) $q->where('numero', 'like', '%'.$request->get('numero').'%');

        // eager loading de reportes (para el desplegable)
        $q->with([
            'reports' => fn($qr) => $qr->withFlowLight()
        ]);

        // campos principales
        $q->select([
            'id','obra_id',
            'anio','numero','fecha',
            'prod_proy','cod_meta','desmeta','desuoper','destipodestino',
            'item','desmedida','cantidad','precio','total','saldo','numero_origen',
            'idsalidadet_silucia','idcompradet_silucia'
        ]);

        $perPage = (int)($request->get('per_page', 20));
        $page    = (int)($request->get('page', 1));
        $p       = $q->orderByDesc('fecha')->paginate($perPage, ['*'], 'page', $page);

        // Transformar reports al shape que consume el front (similar a tu código antiguo)
        $p->getCollection()->transform(function ($item) use ($roles) {
            $item->reports = collect($item->reports)->map(function ($report) use ($roles) {
                $flow        = $report->flow;
                $currentStep = $flow?->steps?->firstWhere('order', (int)$flow->current_step);
                $currentRole = optional($currentStep)->role;

                $userStep = $flow?->steps?->first(function ($s) use ($roles) {
                    return in_array($s->role, $roles, true);
                });

                $canSign = false;
                if ($userStep) {
                    $canSign = $userStep->status === 'pending'
                        && (int)$userStep->order === (int)$flow->current_step;
                }

                return [
                    'report_id'     => $report->id,
                    'type'          => $report->category,
                    'status'        => $report->status,
                    'flow_id'       => $flow?->id,
                    'current_step'  => $flow?->current_step,
                    'current_role'  => $currentRole,
                    'user_step'     => $userStep ? [
                        'id'     => $userStep->id,
                        'role'   => $userStep->role,
                        'status' => $userStep->status,
                        'order'  => $userStep->order,
                        'page'   => $report->pdf_page_number,
                        'pos_x'  => $userStep->pos_x,
                        'pos_y'  => $userStep->pos_y,
                        'width'  => $userStep->width,
                        'height' => $userStep->height,
                    ] : null,
                    'can_sign'       => $canSign,
                    'download_url'   => url("/api/files-download?name={$report->pdf_path}"),
                    'sign_callback_url' => $userStep
                        ? url("/api/signatures/callback?flow_id={$flow->id}&step_id={$userStep->id}&token={$userStep->callback_token}")
                        : null,
                ];
            });

            return $item;
        });

        return response()->json([
            'data'     => $p->items(),
            'total'    => $p->total(),
            'per_page' => $p->perPage(),
        ]);
    }

    public function getKardexManagement(Request $request){
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
    }
    public function getItemPecosas(Request $request, ItemPecosa $itemPecosa){
        // Validar parámetros

        $perPage = (int) $request->query('per_page', 50);

        $query = $itemPecosa->movements()
            ->with([
                    'people' => function ($q) {
                        // IMPORTANTE: incluye la PK 'dni' del related para hidratar bien el modelo
                        $q->select([
                            'people.dni',
                            'people.full_name',
                            'people.names',
                            'people.first_lastname',
                            'people.second_lastname',
                        ]);
                    }
                ])
            ->orderByDesc('movement_date') // fecha más reciente primero
            ->orderByDesc('id');           // y a igualdad de fecha, el último creado

        if ($request->boolean('paginate', true)) {
            $movements = $query->paginate($perPage);
        } else {
            $movements = $query->get();
        }

        return response()->json([
            'item_pecosa'   => [
                'id'                  => $itemPecosa->id,
                'id_order_silucia'    => $itemPecosa->id_order_silucia,
                'id_product_silucia'  => $itemPecosa->id_product_silucia,
                'name'                => $itemPecosa->name,
            ],
            'movements' => $movements,
        ]);
    }

}


// ----------
// app/Http/Controllers/ItemPecosaController.php
