<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementRequest;
use App\Models\MovementKardex;
use App\Models\Person;
use App\Models\Product;
use App\Services\ReniecClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Container\Attributes\Log;
// use Illuminate\Container\Attributes\DB;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        // return $request;
        $data = $request->validated();
        // return $data;
        return DB::transaction(function () use ($request, $data) {

            $sil = $request->input('silucia_product', []);
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

                    'numero'          => $sil['numero']          ?? null,
                    'fecha'           => $sil['fecha']           ?? null,
                    'detalles_orden'  => $sil['detalles_orden']  ?? null,   // ojo: plural
                    'rsocial'         => $sil['rsocial']         ?? null,
                    'ruc'             => $sil['ruc']             ?? null,
                    'item'            => $sil['item']            ?? null,
                    'detalle'         => $sil['detalle']         ?? null,
                    'cantidad'        => $sil['cantidad']        ?? null,
                    'desmedida'       => $sil['desmedida']       ?? null,
                    'precio'          => $sil['precio']          ?? null,
                    'total_internado' => $sil['total_internado'] ?? null,
                    'saldo'           => $sil['saldo']           ?? null,
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
                'observations'  => $data['observations'],
            ]);
            // $movement = MovementKardex::create([
            //     'product_id'    => $product->id,
            //     'movement_type' => $data['movement_type'],
            //     'movement_date' => $data['movement_date'],
            //     'amount'        => $data['amount'],
            //     'final_balance' => $newBalance,
            // ]);

            // 3) Adjuntar personas por DNI (si vino el array y no está vacío)
            $attached = [];
            $missing  = [];

            if (!empty($data['people_dnis']) && is_array($data['people_dnis'])) {
                // normalizar, deduplicar
                $peopleDnis = collect($data['people_dnis'])
                    ->filter() // no nulos/empty
                    ->map(fn ($dni) => str_pad(preg_replace('/\D/','', $dni), 8, '0', STR_PAD_LEFT))
                    ->unique()
                    ->values();

                if ($peopleDnis->isNotEmpty()) {
                    // buscamos solo los que YA están guardados (flujo showOrFetch)
                    $found = \App\Models\Person::whereIn('dni', $peopleDnis)->pluck('dni');

                    // adjuntamos los encontrados (idempotente)
                    $movement->people()->syncWithoutDetaching(
                        $found->mapWithKeys(fn ($dni) => [$dni => ['attached_at' => now()]])->all()
                    );

                    $attached = $found->values()->all();
                    $missing  = $peopleDnis->diff($found)->values()->all(); // DNIs no encontrados en tu BD
                    // Nota: Si quieres intentar traer los "missing" desde RENIEC aquí, se puede,
                    // pero tu flujo actual ya los trae antes con showOrFetch.
                }
            }


            return response()->json([
                'ok'       => true,
                'product'  => $product->only(['id','id_order_silucia','id_product_silucia']),
                'movement' => $movement,
                'movement' => $movement,
                'people'   => [
                    'attached_dnis' => $attached, // DNIs efectivamente vinculados
                    'missing_dnis'  => $missing,  // DNIs que no estaban en BD (no se vincularon)
                ],
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
        // 1) Generar PDF
        $view = 'pdfKardex.reporte';

    $pdf = Pdf::loadView($view, compact('pdf_details'))
              ->setPaper('a4', 'landscape');
              // ->setOption('isRemoteEnabled', true); // si usas imágenes remotas

    // === RUTA PRIVADA CONSISTENTE ===
    // Esto quedará en storage/app/private/silucia_product_reports
    $dir = 'silucia_product_reports';
    Storage::disk('local')->makeDirectory($dir); // asegura carpeta

    $base   = "kardex_{$id_order_silucia}_{$id_product_silucia}";
    $suffix = trim(implode('_', array_filter([
        $type ? "t-{$type}" : null,
        $from ? "from-{$from}" : null,
        $to   ? "to-{$to}"   : null,
        now()->format('Ymd_His'),
    ])), '_');

    $filename     = Str::slug($base . ($suffix ? "_{$suffix}" : ''), '_') . '.pdf';
    $relativePath = "{$dir}/{$filename}";

    // Guardar archivo físico
    $ok = Storage::disk('local')->put($relativePath, $pdf->output());

    // Verificación defensiva
    if (!$ok || !Storage::disk('local')->exists($relativePath)) {
        // Log::error('No se pudo guardar el PDF', ['path' => $relativePath]);
        abort(500, 'No se pudo guardar el PDF.');
    }

    // Guardar solo el nombre del PDF en la columna
    $product->pdf_filename = $filename;
    $product->save();

    // Descargar usando la MISMA disk/relpath (evita errores de ruta)
    return Storage::download($relativePath, $filename);
    // return Storage::disk('local')->download($relativePath, $filename);
        // return Pdf::loadView($view, compact('pdf_details'))
        //     ->setPaper('a4', 'landscape') 
        //     ->download('lista_items_demo.pdf');


        // return $pdfFile->download('anexo02_demo.pdf');
    }

    // obtiene todas las personas de un movimiento
    public function people(MovementKardex $movement)
    {
        return response()->json(
            $movement->people()->orderBy('people.dni')->get()
        );
    }

    // funcion para adjuntar peronas al movimiento
    // attach: si la persona no existe, consulta RENIEC y la crea
    public function attachPerson(Request $request, MovementKardex $movement, ReniecClient $reniec)
    {
        $data = $request->validate([
            'dni'  => ['required','string','regex:/^\d{8}$/'],
            'role' => ['nullable','string','max:100'],
            'note' => ['nullable','string','max:255'],
        ]);

        $dni = $data['dni'];

        $person = Person::find($dni);
        if (!$person) {
            // cache miss → RENIEC
            $payload = $reniec->fetchByDni($dni);
            $ret = data_get($payload, 'consultarResponse.return');
            if (data_get($ret, 'coResultado') !== '0000') {
                return response()->json(['ok'=>false,'message'=>'DNI no encontrado en RENIEC'], 404);
            }
            $dp = data_get($ret, 'datosPersona', []);
            $ubg = explode('/', (string)($dp['ubigeo'] ?? ''));

            $person = Person::create([
                'dni'            => $dni,
                'first_lastname' => $dp['apPrimer']    ?? null,
                'second_lastname'=> $dp['apSegundo']   ?? null,
                'names'          => $dp['prenombres']  ?? null,
                'full_name'      => trim(($dp['prenombres'] ?? '').' '.($dp['apPrimer'] ?? '').' '.($dp['apSegundo'] ?? '')),
                'civil_status'   => $dp['estadoCivil'] ?? null,
                'address'        => $dp['direccion']   ?? null,
                'ubigeo'         => $dp['ubigeo']      ?? null,
                'ubg_department' => $ubg[0] ?? null,
                'ubg_province'   => $ubg[1] ?? null,
                'ubg_district'   => $ubg[2] ?? null,
                'photo_base64'   => $dp['foto']        ?? null,
                'raw'            => $payload,
                'reniec_consulted_at' => now(),
            ]);
        }

        // vincular (idempotente por PK compuesta)
        $movement->people()->syncWithoutDetaching([
            $person->dni => [
                'role' => $data['role'] ?? null,
                'note' => $data['note'] ?? null,
                'attached_at' => now(),
            ]
        ]);

        return response()->json([
            'ok' => true,
            'movement_id' => $movement->id,
            'person' => $person,
        ], 201);
    }
    
    // desvincular persona de movimiento de prodcuto
    public function detachPerson(MovementKardex $movement, string $dni)
    {
        $movement->people()->detach($dni);

        return response()->json(['ok'=>true]);
    }

}




