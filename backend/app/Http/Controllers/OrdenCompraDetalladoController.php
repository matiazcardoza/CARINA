<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obra;
use App\Models\OrdenCompraDetallado;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Utils\UsefulFunctionsForPdfs;
use App\Utils\FpdfExample;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi; 
use Illuminate\Support\Facades\Auth;
use App\Models\SignatureStep;
use Illuminate\Support\Str;

class OrdenCompraDetalladoController extends Controller
{
    function getOrdenesDeCompraDetallado(Request $request, Obra $obra){
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
        $q = OrdenCompraDetallado::query()->where('obra_id', $obra->id);

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
            'id',
            'obra_id',
            'orden_id',
            'idcompradet',
            'anio',
            'numero',
            'siaf',
            'prod_proy',
            'fecha',
            'fecha_aceptacion',
            'item',
            'desmedida',
            'cantidad',
            'precio',
            'saldo',
            'total_internado',
            'internado',
            'idmeta',
            'quantity_received',
            'quantity_issued',
            'quantity_on_hand',
            'external_last_seen_at',
            'external_hash',
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



    // public function getItemPecosas(Request $request, ItemPecosa $itemPecosa){
    public function getMovementKardex(Request $request, OrdenCompraDetallado $ordenCompraDetallado){
        // Validar parámetros
        $perPage = (int) $request->query('per_page', 10);
        // $perPage = 10;
        $page    = (int) $request->query('page', 1);
        // $page    = 1;

        $query = $ordenCompraDetallado->movements()
            ->with([
                'users:id,name,email',
                'users.persona:user_id,num_doc,name,last_name',
            ])
            ->orderByDesc('movement_date') // fecha más reciente primero
            ->orderByDesc('id');           // y a igualdad de fecha, el último creado

        // if ($request->boolean('paginate', true)) {
            // $movements = $query->paginate($perPage, 'page', $page);
            $movements = $query->paginate($perPage, ['*'], 'page', $page);
        // } else {
        //     $movements = $query->get();
        // }

        return response()->json([
            'item_pecosa'   => [
                'id'                  => $ordenCompraDetallado->id,
                'id_order_silucia'    => $ordenCompraDetallado->id_order_silucia,
                'id_product_silucia'  => $ordenCompraDetallado->id_product_silucia,
                'name'                => $ordenCompraDetallado->name,
            ],
            'movements' => $movements,
        ]);
    
    }

    public function pdf(Request $request, OrdenCompraDetallado $ordenCompraDetallado)
    {

        $pecosa = $ordenCompraDetallado;
        $ordenCompraDetallado->load([
            'movements' => function ($q) {
                $q->orderBy('movement_date', 'asc')
                ->orderBy('id', 'asc')
                // Importante: NO uses select() aquí, así no te olvidas de 'created_by'
                ->with([
                    'users:id,name,email',
                    'users.persona:user_id,num_doc,name,last_name',
                    'creator:id,name,email',
                    'creator.persona:user_id,num_doc,name,last_name',
                ]);
            },
        ]);


        $movements = $ordenCompraDetallado->movements;
        $totalEntradas = $movements->where('movement_type','entrada')->sum('amount');
        $totalSalidas  = $movements->where('movement_type','salida')->sum('amount');
        $stockFinal    = $totalEntradas - $totalSalidas;


        /**
         * Guardar en rows[] todas las iflas que iran en el pdf
         */
        // $nombre = Auth::user()->name;
        $nombre = "";
        $rows = [];
        $saldoAcum = 0.0;
        foreach ($movements as $m) {
            $id = $m->id;
            $fecha   = Carbon::parse($m->movement_date)->format('Y-m-d');
            // $tipo    = (string)($m->movement_type ?? '');
            // $monto   = (float)$m->amount;
            // calcular columnas Entrada / Salida
            $movementType = (string)($m->movement_type ?? '');
            $amount       = (float)($m->amount);
            $entrada = $movementType === 'entrada' ? $amount : 0.0;
            $salida  = $movementType === 'salida'  ? $amount : 0.0;
            $saldoAcum   += ($entrada - $salida);
            $firstUser = $m->users->sortBy(fn ($u) => $u->pivot?->attached_at ?? $m->movement_date)->first();
            
            $nombreReceptor = null;
            if($m->movement_type == "salida"){
                $nombreReceptor = $firstUser?->persona?->name
                ? trim($firstUser->persona->name . ' ' . ($firstUser->persona->last_name ?? ''))
                : ($firstUser?->name);
            }else{
                $creator = $m->creator;
                $nombreReceptor = $creator?->persona?->name
                    ? trim($creator->persona->name . ' ' . ($creator->persona->last_name ?? ''))
                    : ($creator?->name);
                if (!$nombreReceptor) {
                    $nombreReceptor = $firstUser?->persona?->name
                        ? trim($firstUser->persona->name . ' ' . ($firstUser->persona->last_name ?? ''))
                        : ($firstUser?->name);
                }
            }
            if (!$nombreReceptor) {
                $nombreReceptor = 'NINUGUNO';
            }

            $obs     = (string)($m->observations ?? '');
            // $rows[]  = [$id, $fecha, $tipo, $monto, $nombreReceptor, $obs];
            $rows[] = [$id, $fecha, $entrada, $salida, $saldoAcum, $nombreReceptor, $obs];
            // $rows[] = [$id, $fecha, $tipo, $monto, $nombreCompleto, $obs];
        }

        // 2) Texto de introducción (USA lo que tengas en product, con fallback)
        //obtenmos la obra 
        
        // $obra       = (string)($pecosa->desmeta ?? '—');
        $obra       = (string)($pecosa->obra->desmeta ?? '—');
        $material   = (string)($pecosa->item ?? '—');
        $comprobante= (string)("OC-{$pecosa->obra->codmeta}" ?? "OC-Indefinido");



        /**
         * QR único por PDF (URL firmada simple)
         * kardex_02874_249069_20250831_021917_10.pdf  ---> id de la orden / id del item / año, mes día /  hora, minuto, segundo / milisegundos
         */
        $basePath = env('PDF_DOWNLOAD_BASE_URL'); 
        $directory = 'silucia_product_reports';
        Storage::disk('local')->makeDirectory($directory);
        $filename = bin2hex(random_bytes(16)) . '.pdf';
        $endpoint = "/api/files-download";

        // $urlPath = $basePath . "/api/" . $directory . "?name=" . $filename;
        $urlPath = $basePath . $endpoint . "?name=" . $filename;
        // $relativePath = "{$directory}/{$filename}";
        // $qrCode = UsefulFunctionsForPdfs::generateQRcode( env('PDF_DOWNLOAD_BASE_URL') . "/api/files-download?name={$filename}");
        $qrCode = UsefulFunctionsForPdfs::generateQRcode($urlPath);

        // 4) Generar PDF con FPDF
        // $headers = ['N', 'Fecha', 'Movimiento', 'Monto', 'Recibido / Encargado', 'Observaciones'];
        $headers = ['N', 'Fecha', 'Entrada', 'Salida', 'Saldo', 'Recibido / Encargado', 'Observaciones'];
        // $widths = [0.1, 0.15, 0.15, 0.15, 0.23, 0.22];
        $widths = [0.07, 0.12, 0.12, 0.12, 0.12, 0.23, 0.22];
        $styles = [
            'lineHeight' => 4,
            'padX'       => 2,
            'padY'       => 1,
            // 'aligns'     => ['C','L','L'],
            'aligns'     => ['C','L','R','R','R','L','L'],
            'border'     => 1,
            'headerFill' => [230,230,230],
        ];

        // antes de que el pdf se cree necesitamos crear el qr e insertarlo
        // http://127.0.0.1:8000/api/files-download?name=kardex_02874_249069_20250829_213601.pdf

        $pdf = new FpdfExample();
        url($filename);
        $pdf->setHeaderLogo($qrCode);
        // $opt = ['rule'=>true];
        $pdf->renderTitle();
        $pdf->drawKardexSummary(
            $obra,
            $material,
            $comprobante,
            $totalEntradas, 
            $totalSalidas, 
            $stockFinal,
            ['labelW'=>38, 'lineHeight'=>5, 'padX'=>2, 'padY'=>1]
        );
        
        $pdf->renderTable($headers, $rows, $widths, $styles);
        $pdf->SignatureBoxTest();
        $bytes = $pdf->Output('S');
        // una vez generado el binario, eliminado el codigo qr (eliminado la imagen qr)
        unlink($qrCode); 
        $ok = Storage::disk('local')->put("{$directory}/{$filename}", $bytes);
        if (!$ok || !Storage::disk('local')->exists("{$directory}/{$filename}")) {
            abort(500, 'No se pudo guardar el PDF.');
        }

        // Contar páginas con FPDI
        $pageCount = 1;
        try {
            $absolute = Storage::disk('local')->path("{$directory}/{$filename}");
            $fpdi = new Fpdi();
            $pageCount = (int)$fpdi->setSourceFile($absolute);
        } catch (\Throwable $e) {
            Log::info("No se pudo contar páginas de {{$directory}/{$filename}}: ".$e->getMessage());
        }
        $report = Report::create([
            'reportable_id'    => $pecosa->id,
            'reportable_type'  => ordenCompraDetallado::class,
            // 'pdf_path'         => $relativePath,   
            'pdf_path'         => $urlPath,   
            'pdf_page_number'  => $pageCount,
            'status'           => 'in_progress',
            'created_by'       => Auth::id(),
        ]);

        $roles = config('signing.roles_order', [
            ['role'=>'almacen.almacenero',       'page'=>1,'pos_x'=>35,  'pos_y'=>745,'width'=>180,'height'=>60],
            ['role'=>'almacen.administrador',   'page'=>1,'pos_x'=>170, 'pos_y'=>745,'width'=>180,'height'=>60],
            ['role'=>'almacen.residente',       'page'=>1,'pos_x'=>305, 'pos_y'=>745,'width'=>180,'height'=>60],
            ['role'=>'almacen.supervisor',      'page'=>1,'pos_x'=>440, 'pos_y'=>745,'width'=>180,'height'=>60],
        ]);
        foreach (array_values($roles) as $i => $role) {
            SignatureStep::create([
                'report_id'         => $report->id,
                'order'             => $i+1,
                'role'              => $role['role'],
                'page'              => $role['page'],
                'pos_x'             => $role['pos_x'],
                'pos_y'             => $role['pos_y'],
                'width'             => $role['width'],
                'height'            => $role['height'],
                'callback_token'    => Str::random(48),
            ]);
        }

        return Storage::download("{$directory}/{$filename}", $filename);
    }
}
