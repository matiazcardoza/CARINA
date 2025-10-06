<?php

namespace App\Http\Controllers;

use App\Models\ItemPecosa;
use App\Models\Obra;
use App\Services\PecosaClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function testPecosas(Request $request, Obra $obra){
        /**
         * Verifica si la obra esta anexada al usuario, si no lo esta, entonces debe mostrarse un mensaje de error.
         * Pero esta verificación ya esta siendo realizada en el middleware "ResolveCurrentObra"
         */

        $user  = $request->user();
        $roles = $user->getRoleNames()->toArray();
        $isOperator = $user->hasRole('almacen.almacenero');

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
            // se debe modificar, solo entregar aquellos que tieene un reporte
            $q->whereHas('reports'); // al menos 1 reporte con flow
        }
        
        // filtros UI (opcionales)
        if ($request->filled('anio'))   $q->where('anio', $request->integer('anio'));
        
        if ($request->filled('numero')) $q->where('numero', 'like', '%'.$request->get('numero').'%');

        $q->with('reports.steps');

        $q->select([
            'id','obra_id',
            'anio','numero','fecha',
            'quantity_received', 'quantity_issued', 'quantity_on_hand',
            'prod_proy','cod_meta','desmeta','desuoper','destipodestino',
            'item','desmedida','cantidad','precio','total','saldo','numero_origen',
            'idsalidadet_silucia','idcompradet_silucia'
        ]);


        
        $perPage = (int)($request->get('per_page', 20));
        $page    = (int)($request->get('page', 1));
        $p       = $q->orderByDesc('fecha')->paginate($perPage, ['*'], 'page', $page);

        $p->getCollection()->transform(function ($item) use ($user, $roles) {
            // $item->reports es una Collection de Report
            $item->reports->each(
                function ($r)  use ($user, $roles) {
                    if ($r->relationLoaded('steps')) {
                        $curr = $r->steps->firstWhere('order', $r->current_step);
                        // $curr = $r->steps->firstWhere('role', $r->current_step);
                        $r->setRelation('currentStep', $curr);
                        // opcional: no enviar todos los steps al frontend
                        $r->unsetRelation('steps');
                        if ($curr) {
                            $qs = http_build_query([
                                'report_id'     => $r->id,
                                'step_id'       => $curr->id,
                                'user_id'       => $user->id,
                                'user_roles'    => $roles,
                                'token'         => $curr->callback_token,
                            ]);
                            $r->setAttribute('sign_callback_url', env('PDF_DOWNLOAD_BASE_URL')."/api/signatures/callback?{$qs}");
                        } else {
                            $r->setAttribute('sign_callback_url', null);
                        }
                    }
                    if($user->hasRole($curr->role)){
                        $r->can_you_sign = true;
                    }else{
                        $r->can_you_sign = false;
                    }
                    // if ($user->hasRole('almacen.almacenero') && $r->current_step == 1){
                    //     $r->can_you_sign = true;
                    // }elseif($user->hasRole('almacen.administrador') && $r->current_step == 2){
                    //     $r->can_you_sign = true;
                    // }elseif($user->hasRole('almacen.residente') && $r->current_step == 3){
                    //     $r->can_you_sign = true;
                    // }elseif($user->hasRole('almacen.supervisor') && $r->current_step == 4){
                    //     $r->can_you_sign = true;
                    // }else{
                    //     $r->can_you_sign = false;
                    // }
                });
            $item->number_reports = $item->reports->count();
            return $item;
        });

        return response()->json([
            'data'     => $p->items(),
            'total'    => $p->total(),
            'per_page' => $p->perPage(),
        ]);
    }

    public function getItemPecosas(Request $request, ItemPecosa $itemPecosa){
        // Validar parámetros

        $perPage = (int) $request->query('per_page', 50);

        $query = $itemPecosa->movements()
            ->with([
                'users:id,name,email',
                'users.persona:user_id,num_doc,name,last_name',
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
