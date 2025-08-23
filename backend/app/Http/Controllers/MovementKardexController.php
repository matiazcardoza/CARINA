<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementRequest;
use App\Models\MovementKardex;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
// use Illuminate\Container\Attributes\DB;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MovementKardexController extends Controller
{
    public function index(Product $product)
    {
        // /api/products/1/movements-kardex
        $movements = $product->movements()
            ->orderByDesc('movement_date')  // si no hay fecha, usa ->latest()
            ->orderByDesc('id')
            ->get();

        return response()->json($movements);
    }
    /**
     * debe eliminarse despues, ya no se usara
     */
    public function storeForProduct(Request $request, Product $product)
    {
        // return $product;
        $validated = $request->validate([
            // 'fecha' => 'required|date',
            'movement_type' => 'required|in:entrada,salida',
            'amount' => 'required|numeric|min:0',
            // 'costo_unitario' => 'required|numeric|min:0',
            // 'referencia' => 'nullable|string'
        ]);

        $entry = MovementKardex::create([
            ...$validated,
            'product_id' => $product->id,
            'movement_date' => now()
        ]);

        return response()->json([
            'message' => 'Movimiento registrado correctamente',
            'data' => $entry
        ], 201);
    }

    /**
     * Crea un movimiento. Si el “gancho” Product no existe, lo crea.
     * Reglas:
     * - El par (id_order_silucia, id_product_silucia) es único en products.
     * - Si ya existe con otro order_id local => 409 (conflicto).
     * - Calcula final_balance a partir del último movimiento del producto.
     */
    public function store(StoreMovementRequest $request)
    {
        // return $request;
        $data = $request->validated();
        // return $data;
        return DB::transaction(function () use ($data) {
            // 1) Buscar o crear el “gancho” del producto por la pareja SILUCIA
            $product = Product::firstOrCreate(
                [
                                                //    id_order_silucia
                    'id_order_silucia'   => $data['id_order_silucia'],
                    'id_product_silucia' => $data['id_product_silucia'],
                ],
                [
                    // Solo se setean al crear; si luego quieres actualizar, puedes hacerlo abajo
                    // estos datos no sirven para nada, deben eliminarse de la tabla productos
                    'name'          => $data['name']          ?? null,
                    'heritage_code' => $data['heritage_code'] ?? null,
                    'unit_price'    => $data['unit_price']    ?? null,
                    // 'quantity'      => $data['quantity']      ?? null,
                    'state'         => 1,
                ]
            );

            // (Opcional) Actualizar “esenciales” si vinieron y el producto ya existía
            $product->fill(array_filter([
                'name'          => $data['name']          ?? null,
                'heritage_code' => $data['heritage_code'] ?? null,
                'unit_price'    => $data['unit_price']    ?? null,
                // 'quantity'      => $data['quantity']      ?? null,
            ], fn($v) => !is_null($v)))->save();

            // 2) Calcular saldo final automáticamente
            // Bloqueo para consistencia si hay alta concurrencia
            // $last = MovementKardex::where('product_id', $product->id)
            //        ->lockForUpdate()
            //        ->orderByDesc('id')
            //        ->first();

            // $prevBalance = $last->final_balance ?? 0;
            // $newBalance  = $data['movement_type'] === 'IN'
            //              ? $prevBalance + $data['amount']
            //              : $prevBalance - $data['amount'];

            // (Opcional) Evitar saldo negativo
            // if ($newBalance < 0) abort(422, 'El movimiento genera saldo negativo.');

            // 3) Crear movimiento
            $movement = MovementKardex::create([

                'product_id' => $product->id,
                'movement_date' => now(),
                'movement_type' => $data['movement_type'],
                'amount'        => $data['amount'],
                'observations'        => $data['observations'],
            ]);
            // $movement = MovementKardex::create([
            //     'product_id'    => $product->id,
            //     'movement_type' => $data['movement_type'],
            //     'movement_date' => $data['movement_date'],
            //     'amount'        => $data['amount'],
            //     'final_balance' => $newBalance,
            // ]);

            return response()->json([
                'ok'       => true,
                'product'  => $product->only([
                    'id','id_order_silucia','id_product_silucia'
                ]),
                'movement' => $movement,
            ], 201);
        });
    }


    public function indexBySiluciaIds(Request $request, $id_order_silucia, $id_product_silucia)
    {
        // 1) Buscar el “gancho” Product por la pareja de SILUCIA
        $product = Product::where('id_order_silucia', $id_order_silucia)
            ->where('id_product_silucia', $id_product_silucia)
            ->firstOrFail(); // 404 si no existe

        // 2) Traer movimientos (puedes paginar si quieres)
        //    Si quieres TODO: ->get();
        //    Si prefieres paginar: ?per_page=20
        $perPage = (int) $request->query('per_page', 50);

        $query = $product->movements()
            ->orderByDesc('movement_date') // fecha más reciente primero
            ->orderByDesc('id');           // y a igualdad de fecha, el último creado

        if ($request->boolean('paginate', true)) {
            $movements = $query->paginate($perPage);
        } else {
            $movements = $query->get();
        }

        return response()->json([
            'product'   => [
                'id'                  => $product->id,
                'id_order_silucia'    => $product->id_order_silucia,
                'id_product_silucia'  => $product->id_product_silucia,
                'name'                => $product->name,
            ],
            'movements' => $movements,
        ]);
    }

    public function pdf(Request $request, $id_order_silucia, $id_product_silucia){

        // Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex/pdf',  [MovementKardexController::class, 'pdf']);

        $from = $request->query('from');
        $to   = $request->query('to');
        $type = $request->query('type'); // 'entrada' | 'salida

        $product = Product::where('id_order_silucia', $id_order_silucia)
            ->where('id_product_silucia', $id_product_silucia)
            ->firstOrFail(); // 404 si no existe

        // Cargar la relación con filtros y orden
        $product->load(['movements' => function ($q) use ($from, $to, $type) {
            if ($from) $q->whereDate('movement_date', '>=', $from);
            if ($to)   $q->whereDate('movement_date', '<=', $to);
            if ($type) $q->where('movement_type', $type);

            $q->orderBy('movement_date', 'asc')
            ->select([
                'id',
                'product_id',
                'movement_date',
                'class',
                'number',
                'movement_type',
                'amount',
                'observations'
            ]);
        }]);


        // los movements son los movimiento kardex de cada producto
        $movements = $product->movements;
        $totalEntradas = $movements->where('movement_type','entrada')->sum('amount');
        $totalSalidas  = $movements->where('movement_type','salida')->sum('amount');
        $stockFinal    = $totalEntradas - $totalSalidas;

        $pdf_details = [
            'product'       => $product,
            'movements'     => $movements,
            'totalEntradas' => $totalEntradas,
            'totalSalidas'  => $totalSalidas,
            'stockFinal'    => $totalEntradas - $totalSalidas,
        ];
        $view = 'pdfKardex.reporte'; // <-- cámbialo al nombre real de tu plantilla

        return Pdf::loadView($view, compact('pdf_details'))
            ->setPaper('a4', 'landscape') // o 'landscape' si prefieres horizontal
            ->download('lista_items_demo.pdf');


        return $pdfFile->download('anexo02_demo.pdf');
    }


}




    // EJEMPLO UNO
        // // -------- Empresa (mock) --------
        // $empresa = (object)[
        //     'razon_social' => 'Gobierno Regional Puno - Proyecto de Demostración',
        //     'ruc'          => '20123456789',
        //     'logo'         => 'logo_demo.png', // storage/logo_demo.png (usa storage:link)
        // ];

        // // -------- Mes/Año/Proyecto (mock) --------
        // $anio = 2025;
        // $month = 8; // Agosto
        // $mes = 'AGOSTO';
        // $proyecto = 'Mejoramiento de Infraestructura Vial Urbana – Zona Central';

        // // -------- Cabeceras (días) --------
        // $inicioMes = Carbon::create($anio, $month, 1);
        // $finMes    = (clone $inicioMes)->endOfMonth();
        // $periodo   = CarbonPeriod::create($inicioMes, '1 day', $finMes);

        // $mapDia = [1=>'L', 2=>'M', 3=>'X', 4=>'J', 5=>'V', 6=>'S', 7=>'D'];
        // $diasNumeros = [];
        // $diasLetras  = [];
        // foreach ($periodo as $dia) {
        //     $diasNumeros[] = (int)$dia->format('d');
        //     $diasLetras[]  = $mapDia[$dia->dayOfWeekIso];
        // }
        // // La vista espera $cabeceras con esta forma:
        // $cabeceras = [[
        //     'nombre_dias' => $diasLetras,
        //     'dias'        => $diasNumeros,
        // ]];

        // // -------- Datos de cabecera de “responsables” que usa la vista --------
        // $tipo = (object)[
        //     'nombre' => 'EJECUCIÓN DE OBRA',
        //     'codigo' => 'OBRA', // forzamos rama "else" (no GASGES/EXPTEC)
        // ];
        // $tipo_asistencia = 'ASISTENCIA REGULAR';

        // // Si id es 49 o 48 la vista escribe "GERENCIA REGIONAL DE INFRAESTRUCTURA"
        // $oficina = (object)[
        //     'id'     => 49,
        //     'nombre' => 'Gerencia Regional de Infraestructura',
        // ];

        // $proyecto_completo = (object)[
        //     'cui'     => '2512345',
        //     'pliego'  => '458 - Gobierno Regional Puno',
        //     'fte_fto' => 'Recursos Ordinarios',
        // ];
        // $meta = 'Meta 001';
        // $tareo = (object)[ 'fte_fto' => null ]; // null => usa $proyecto_completo->fte_fto

        // // Personas base
        // $base = [
        //     ['dni'=>'40781234', 'nombres'=>'ANA',    'ap_paterno'=>'QUISPE', 'ap_materno'=>'MAMANI', 'cargo'=>'ASISTENTE', 'nac'=>'1994-05-12'],
        //     ['dni'=>'45678901', 'nombres'=>'CARLOS', 'ap_paterno'=>'HUANCA', 'ap_materno'=>'QUISPE', 'cargo'=>'SUPERVISOR', 'nac'=>'1989-11-03'],
        //     ['dni'=>'42345678', 'nombres'=>'MARÍA',  'ap_paterno'=>'APAZA',  'ap_materno'=>'FLORES', 'cargo'=>'TÉCNICO', 'nac'=>'1992-02-21'],
        //     ['dni'=>'41239876', 'nombres'=>'JOSÉ',   'ap_paterno'=>'COILA',  'ap_materno'=>'RAMOS',  'cargo'=>'OPERARIO', 'nac'=>'1990-07-09'],
        //     ['dni'=>'43876543', 'nombres'=>'LUCÍA',  'ap_paterno'=>'CHOQUE', 'ap_materno'=>'QUENTA', 'cargo'=>'ASISTENTE', 'nac'=>'1995-09-30'],
        // ];

        // // Asistencias: S y D = F, resto = A + 1 falta aleatoria
        // $personal = [];
        // foreach ($base as $p) {
        //     $asistencias = [];
        //     $laborablesIdx = [];
        //     $idx = 0;

        //     foreach (CarbonPeriod::create($inicioMes, '1 day', $finMes) as $dia) {
        //         $isWeekend = in_array($dia->dayOfWeekIso, [6,7]);
        //         if ($isWeekend) {
        //             $asistencias[] = 'F';
        //         } else {
        //             $asistencias[] = 'A';
        //             $laborablesIdx[] = $idx;
        //         }
        //         $idx++;
        //     }
        //     if (!empty($laborablesIdx)) {
        //         $randomKey = $laborablesIdx[array_rand($laborablesIdx)];
        //         $asistencias[$randomKey] = 'F';
        //     }

        //     $tot_asis = 0;
        //     foreach ($asistencias as $a) if ($a === 'A') $tot_asis++;

        //     $personal[] = [
        //         'num_doc'        => $p['dni'],
        //         'nombres'        => $p['nombres'],
        //         'ap_paterno'     => $p['ap_paterno'],
        //         'ap_materno'     => $p['ap_materno'],
        //         'fec_nacimiento' => $p['nac'],
        //         'cargo'          => $p['cargo'],
        //         'asistencias'    => $asistencias,
        //         'tot_asis'       => $tot_asis,
        //     ];
        // }

        // // Quienes firma/encargados para la rama "OBRA"
        // $supervisor = (object)['nombre_completo' => 'Ing. Pedro Quispe Condori'];
        // $residente  = (object)['nombre_completo' => 'Ing. Rosa Mamani Tito'];
        // $inspector  = null; // no se usa si hay supervisor
        // $jefe_proyecto = null; // solo para EXPTEC

        // // Páginas r1, r2, r3: vacías para no generar más páginas
        // $r1_personal = $r2_personal = $r3_personal = [];
        // $r1_cabeceras = $r2_cabeceras = $r3_cabeceras = [];
        // $r1_mes = $r2_mes = $r3_mes = null;
        // $r1_anio = $r2_anio = $r3_anio = null;
        // $r1_tipo_asistencia = $r2_tipo_asistencia = $r3_tipo_asistencia = null;
        // $r1_supervisor = $r2_supervisor = $r3_supervisor = null;
        // $r1_inspector  = $r2_inspector  = $r3_inspector  = null;
        // $r1_residente  = $r2_residente  = $r3_residente  = null;
        // $r1_jefe_proyecto = $r2_jefe_proyecto = $r3_jefe_proyecto = null;

        // // Flags + QR
        // $pdf   = true;
        // $excel = false;
        // $qr_code = ''; // si usas package, pon aquí el base64

        // $payload = compact(
        //     'empresa','excel','qr_code','pdf',
        //     'mes','anio','proyecto','cabeceras','personal',
        //     'tipo','tipo_asistencia','oficina',
        //     'proyecto_completo','meta','tareo',
        //     'supervisor','residente','inspector','jefe_proyecto',
        //     'r1_personal','r1_cabeceras','r1_mes','r1_anio','r1_tipo_asistencia','r1_supervisor','r1_inspector','r1_residente','r1_jefe_proyecto',
        //     'r2_personal','r2_cabeceras','r2_mes','r2_anio','r2_tipo_asistencia','r2_supervisor','r2_inspector','r2_residente','r2_jefe_proyecto',
        //     'r3_personal','r3_cabeceras','r3_mes','r3_anio','r3_tipo_asistencia','r3_supervisor','r3_inspector','r3_residente','r3_jefe_proyecto'
        // );