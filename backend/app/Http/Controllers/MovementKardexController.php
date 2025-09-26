<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementPecosaRequest;
use App\Http\Requests\StoreMovementRequest;
use App\Models\FuelOrder;
use App\Models\ItemPecosa;
// use App\Http\Requests\StoreMovementPecosaRequest;
use App\Models\KardexReport;
use App\Models\MovementKardex;
use App\Models\Person;
use App\Models\Product;
use App\Models\Report;
use App\Models\SignatureEvent;
use App\Models\SignatureFlow;
use App\Models\SignatureStep;
use App\Services\ReniecClient;
use App\Utils\FpdfExample;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
// use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\Log;
// use Illuminate\Container\Attributes\DB;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
// use setasign\Fpdi\Tcpdf\Fpdi;
// use setasign\Fpdi\Fpdi;
// use Codedge\Fpdf\Fpdf\Fpdf;
use setasign\Fpdi\Fpdi; 
use App\Utils\KardexReportPdf;

use App\Utils\UsefulFunctionsForPdfs;
// use App\Models\User;
// use Illuminate\Support\Facades\Auth;
// namespace App\Models;
// use Codedge\Fpdf\Fpdf\Fpdf;
// use setasign\Fpdi\Fpdf\Fpdf;
// use setasign\Fpdi\Fpdi;
// use App\Http\Controllers\Fpdf;
// use Codedge\Fpdf\Fpdf\FPDF;  // Ojo: aquí la clase se llama FPDF (mayúsculas)
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
    public function storev01(StoreMovementRequest $request)
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
                    'desmeta'         => $sil['desmeta']         ?? null, // <-- NUEVO
                ]
            );

            // (Opcional) Actualizar “esenciales” si vinieron y el producto ya existía
            $product->fill(array_filter([
                'name'          => $data['name']          ?? null,
                'heritage_code' => $data['heritage_code'] ?? null,
                'unit_price'    => $data['unit_price']    ?? null,
                'desmeta'       => $sil['desmeta']        ?? null, // <-- NUEVO
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
    
    public function store_version_para_producto(StoreMovementRequest $request)
    {
        // Log::info(request);
        $data = $request->validated();
        return DB::transaction(function () use ($request, $data) {

            $sil = $request->input('silucia_product', []);

            // 1) Buscar producto por par SILUCIA con LOCK para consistencia
            $product = Product::where('id_order_silucia', $data['id_order_silucia'])
                ->where('id_product_silucia', $data['id_product_silucia'])
                ->lockForUpdate()
                ->first();

            if (!$product) {
                // Si no existe, lo creamos (ya "bloqueado" por la transacción)
                $product = Product::create([
                    'id_order_silucia'   => $data['id_order_silucia'],
                    'id_product_silucia' => $data['id_product_silucia'],

                    'name'          => $data['name']          ?? null,
                    'heritage_code' => $data['heritage_code'] ?? null,
                    'unit_price'    => $data['unit_price']    ?? null,
                    'state'         => 1,

                    'numero'          => $sil['numero']          ?? null,
                    'fecha'           => $sil['fecha']           ?? null,
                    'detalles_orden'  => $sil['detalles_orden']  ?? null,
                    'rsocial'         => $sil['rsocial']         ?? null,
                    'ruc'             => $sil['ruc']             ?? null,
                    'item'            => $sil['item']            ?? null,
                    'detalle'         => $sil['detalle']         ?? null,
                    'cantidad'        => $sil['cantidad']        ?? null,
                    'desmedida'       => $sil['desmedida']       ?? null,
                    'precio'          => $sil['precio']          ?? null,
                    'total_internado' => $sil['total_internado'] ?? null,
                    'saldo'           => $sil['saldo']           ?? null,
                    'desmeta'         => $sil['desmeta']         ?? null,
                ]);
            } else {
                // Actualiza esenciales si vienen
                $product->fill(array_filter([
                    'name'          => $data['name']          ?? null,
                    'heritage_code' => $data['heritage_code'] ?? null,
                    'unit_price'    => $data['unit_price']    ?? null,
                    'desmeta'       => $sil['desmeta']        ?? null,
                ], fn($v) => !is_null($v)))->save();
            }

            // 2) Deltas según tipo
            $isEntrada = $data['movement_type'] === 'entrada';
            $amount    = (float) $data['amount'];

            $deltaIn  = $isEntrada ? $amount : 0;
            $deltaOut = $isEntrada ? 0       : $amount;

            // 3) Validación de stock (no permitir negativos)
            $newIn   = (float) $product->in_qty  + $deltaIn;
            $newOut  = (float) $product->out_qty + $deltaOut;
            $newStock= $newIn - $newOut;

            if ($newStock < 0) {
                abort(422, 'El movimiento genera stock negativo.');
            }

            // 4) Crear movimiento
            $movement = MovementKardex::create([
                'product_id'    => $product->id,
                'movement_date' => now(),
                'movement_type' => $data['movement_type'],   // 'entrada' | 'salida'
                'amount'        => $amount,
                'observations'  => $data['observations'] ?? null,
                // Si tu tabla tiene 'final_balance', guarda el stock luego de aplicar el movimiento:
                // 'final_balance'  => $newStock,
            ]);

            // 5) Actualizar contadores en products
            $product->forceFill([
                'in_qty'          => $newIn,
                'out_qty'         => $newOut,
                'stock_qty'       => $newStock,
                'last_movement_at'=> now(),
            ])->save();

            // 6) Adjuntar personas (tu bloque actual tal cual)
            $attached = [];
            $missing  = [];
            if (!empty($data['people_dnis']) && is_array($data['people_dnis'])) {
                $peopleDnis = collect($data['people_dnis'])
                    ->filter()
                    ->map(fn ($dni) => str_pad(preg_replace('/\D/','', $dni), 8, '0', STR_PAD_LEFT))
                    ->unique()
                    ->values();

                if ($peopleDnis->isNotEmpty()) {
                    $found = \App\Models\Person::whereIn('dni', $peopleDnis)->pluck('dni');
                    $movement->people()->syncWithoutDetaching(
                        $found->mapWithKeys(fn ($dni) => [$dni => ['attached_at' => now()]])->all()
                    );
                    $attached = $found->values()->all();
                    $missing  = $peopleDnis->diff($found)->values()->all();
                }
            }

            return response()->json([
                'ok'       => true,
                'product'  => $product->only(['id','id_order_silucia','id_product_silucia','in_qty','out_qty','stock_qty']),
                'movement' => $movement,
                'people'   => [
                    'attached_dnis' => $attached,
                    'missing_dnis'  => $missing,
                ],
            ], 201);
        });
    }

    public function storev0(StoreMovementPecosaRequest $request)
    {

        // return $request;
        $data = $request->validated();
        return DB::transaction(function () use ($request, $data) {

            $sil = $request->input('silucia_pecosa', []);

            // Normaliza tipos clave
            $data['id_pecosa_silucia'] = (string) $data['id_pecosa_silucia'];
            $amount = (float) $data['amount'];
            $isEntrada = $data['movement_type'] === 'entrada';

            // 1) Buscar producto por par SILUCIA con LOCK para consistencia

            $item_pecosa = ItemPecosa::where('id_pecosa_silucia', $data['id_pecosa_silucia'])
                ->where('id_item_pecosa_silucia', $data['id_item_pecosa_silucia'])
                ->lockForUpdate()
                ->first();

            $wasCreated = false;

            if (!$item_pecosa) {
                // Si no existe, lo creamos (ya "bloqueado" por la transacción)
                $item_pecosa = ItemPecosa::create([
                    'id_pecosa_silucia'   => $data['id_pecosa_silucia'],
                    'id_item_pecosa_silucia' => $data['id_item_pecosa_silucia'],

                    'anio'              => $sil['anio']          ?? null,
                    'numero'            => $sil['numero']        ?? null,
                    'fecha'             => $sil['fecha']         ?? null,
                    'prod_proy'         => $sil['prod_proy']     ?? null,
                    'cod_meta'          => $sil['cod_meta']       ?? null,
                    'desmeta'           => $sil['desmeta']        ?? null,
                    'desuoper'          => $sil['desuoper']       ?? null,
                    'destipodestino'    => $sil['destipodestino'] ?? null,
                    'item'              => $sil['item']           ?? null,
                    'desmedida'         => $sil['desmedida']      ?? null,
                    'idsalidadet'       => $sil['idsalidadet']    ?? null,
                    'cantidad'          => $sil['cantidad']       ?? null,
                    'precio'            => $sil['precio']         ?? null,
                    'tipo'              => $sil['tipo']           ?? null,
                    'saldo'             => $sil['saldo']          ?? null,
                    'total'             => $sil['total']          ?? null,
                    'numero_origen'     => $sil['numero_origen']  ?? null,
                ]);
                $wasCreated = false;
                $initial = (float) ($sil['cantidad'] ?? 0);
                $item_pecosa->forceFill([
                    'quantity_received' => $initial,
                    'quantity_issued'   => 0,
                    'quantity_on_hand'  => $initial,
                ])->save();
            } else {
                // Actualiza esenciales si vienen
                $item_pecosa->fill(array_filter([
                    'id_pecosa_silucia'   => $data['id_pecosa_silucia'],
                    'id_item_pecosa_silucia' => $data['id_item_pecosa_silucia'],
                    'anio'              => $sil['anio']          ?? null,
                    'numero'            => $sil['numero']        ?? null,
                    'fecha'             => $sil['fecha']         ?? null,
                    'prod_proy'         => $sil['prod_proy']     ?? null,
                    'cod_meta'          => $sil['cod_meta']       ?? null,
                    'desmeta'           => $sil['desmeta']        ?? null,
                    'desuoper'          => $sil['desuoper']       ?? null,
                    'destipodestino'    => $sil['destipodestino'] ?? null,
                    'item'              => $sil['item']           ?? null,
                    'desmedida'         => $sil['desmedida']      ?? null,
                    'idsalidadet'       => $sil['idsalidadet']    ?? null,
                    'cantidad'          => $sil['cantidad']       ?? null,
                    'precio'            => $sil['precio']         ?? null,
                    'tipo'              => $sil['tipo']           ?? null,
                    'saldo'             => $sil['saldo']          ?? null,
                    'total'             => $sil['total']          ?? null,
                    'numero_origen'     => $sil['numero_origen']  ?? null,
                ], fn($v) => !is_null($v)))->save();
                // Si nunca se inicializaron contadores y ahora sí tenemos 'cantidad', haz bootstrap una vez
                if (($item_pecosa->quantity_received ?? 0) == 0 
                    && ($item_pecosa->quantity_issued ?? 0) == 0 
                    && ($item_pecosa->cantidad ?? null) !== null) {
                    $initial = (float) $item_pecosa->cantidad;
                    $item_pecosa->forceFill([
                        'quantity_received' => $initial,
                        'quantity_issued'   => 0,
                        'quantity_on_hand'  => $initial,
                    ])->save();
                }
            }
            
            


            // Log::info($item_pecosa);
            // 2) Deltas según tipo
            $isEntrada = $data['movement_type'] === 'entrada';
            $amount    = (float) $data['amount'];

            $deltaIn  = $isEntrada ? $amount : 0;
            $deltaOut = $isEntrada ? 0       : $amount;

            // 3) Validación de stock (no permitir negativos)
            // $newIn   = (float) $item_pecosa->in_qty  + $deltaIn;
            // $newOut  = (float) $item_pecosa->out_qty + $deltaOut;
            // $newStock= $newIn - $newOut;

            $currentIn   = (float) ($item_pecosa->quantity_received ?? 0);
            $currentOut  = (float) ($item_pecosa->quantity_issued   ?? 0);

            $newIn    = $currentIn  + $deltaIn;
            $newOut   = $currentOut + $deltaOut;
            $newStock = $newIn - $newOut;



            if ($newStock < 0) {
                abort(422, 'El movimiento genera stock negativo.');
            }

            // 4) Crear movimiento
            $movement = MovementKardex::create([
                // 'product_id'    => $item_pecosa->id,
                'item_pecosa_id'   => $item_pecosa->id,
                'movement_date' => now(),
                'movement_type' => $data['movement_type'],   // 'entrada' | 'salida'
                'amount'        => $amount,
                'observations'  => $data['observations'] ?? null,
                // Si tu tabla tiene 'final_balance', guarda el stock luego de aplicar el movimiento:
                // 'final_balance'  => $newStock,
            ]);

            // 5) Actualizar contadores en products
            $item_pecosa->forceFill([
                'quantity_received' => $newIn,
                'quantity_issued'   => $newOut,
                'quantity_on_hand'  => $newStock,
                'last_movement_at'  => now(),
            ])->save();


            // 6) Adjuntar personas (tu bloque actual tal cual)
            $attached = [];
            $missing  = [];
            if (!empty($data['people_dnis']) && is_array($data['people_dnis'])) {
                $peopleDnis = collect($data['people_dnis'])
                    ->filter()
                    ->map(fn ($dni) => str_pad(preg_replace('/\D/','', $dni), 8, '0', STR_PAD_LEFT))
                    ->unique()
                    ->values();

                if ($peopleDnis->isNotEmpty()) {
                    $found = \App\Models\Person::whereIn('dni', $peopleDnis)->pluck('dni');
                    $movement->people()->syncWithoutDetaching(
                        $found->mapWithKeys(fn ($dni) => [$dni => ['attached_at' => now()]])->all()
                    );
                    $attached = $found->values()->all();
                    $missing  = $peopleDnis->diff($found)->values()->all();
                }
            }

            return response()->json([
                'ok'       => true,
                'item_pecosa'  => $item_pecosa->only(['id','id_pecosa_silucia','id_item_pecosa_silucia','quantity_received','quantity_issued','quantity_on_hand']),
                'movement' => $movement,
                'people'   => [
                    'attached_dnis' => $attached,
                    'missing_dnis'  => $missing,
                ],
            ], 201);
        });
    }
// public function pecosas(Request $request, OrdenCompra $orden)
    public function store(StoreMovementPecosaRequest $request, ItemPecosa $itemPecosa)
    {
        $data = $request->validated();
        return DB::transaction(function () use ($data, $itemPecosa) {
            // Normaliza tipos clave
            // $data['id_pecosa_silucia'] = (string) $data['id_pecosa_silucia'];
            $amount = (float) $data['amount'];
            $isEntrada = $data['movement_type'] === 'entrada';

            // Log::info($item_pecosa);
            // 2) Deltas según tipo

            $deltaIn  = $isEntrada ? $amount : 0;
            $deltaOut = $isEntrada ? 0       : $amount;



            // $currentIn   = (float) ($item_pecosa->quantity_received ?? 0);
            // $currentOut  = (float) ($item_pecosa->quantity_issued   ?? 0);

            // $newIn    = $currentIn  + $deltaIn;
            // $newOut   = $currentOut + $deltaOut;
            // $newStock = $newIn - $newOut;

            // 4) Crear movimiento
            $movement = MovementKardex::create([
                'item_pecosa_id'   => $itemPecosa->id,
                'movement_date' => now(),
                'movement_type' => $data['movement_type'],   // 'entrada' | 'salida'
                'amount'        => $amount,
                'observations'  => $data['observations'] ?? null,

            ]);

            // 5) Actualizar contadores en products
            // $item_pecosa->forceFill([
            //     'quantity_received' => $newIn,
            //     'quantity_issued'   => $newOut,
            //     'quantity_on_hand'  => $newStock,
            //     'last_movement_at'  => now(),
            // ])->save();


            // 6) Adjuntar personas (tu bloque actual tal cual)
            $attached = [];
            $missing  = [];
            if (!empty($data['people_dnis']) && is_array($data['people_dnis'])) {
                $peopleDnis = collect($data['people_dnis'])
                    ->filter()
                    ->map(fn ($dni) => str_pad(preg_replace('/\D/','', $dni), 8, '0', STR_PAD_LEFT))
                    ->unique()
                    ->values();

                if ($peopleDnis->isNotEmpty()) {
                    $found = \App\Models\Person::whereIn('dni', $peopleDnis)->pluck('dni');
                    $movement->people()->syncWithoutDetaching(
                        $found->mapWithKeys(fn ($dni) => [$dni => ['attached_at' => now()]])->all()
                    );
                    $attached = $found->values()->all();
                    $missing  = $peopleDnis->diff($found)->values()->all();
                }
            }

            return response()->json([
                'ok'       => true,
                'item_pecosa'  => $itemPecosa->only(['id','id_pecosa_silucia','id_item_pecosa_silucia','quantity_received','quantity_issued','quantity_on_hand']),
                'movement' => $movement,
                'people'   => [
                    'attached_dnis' => $attached,
                    'missing_dnis'  => $missing,
                ],
            ], 201);
        });
    }


    // public function indexBySiluciaIds(Request $request, $id_order_silucia, $id_product_silucia)
    public function indexBySiluciaIds(Request $request, $pecosaId, $itemId)
    {

        // return "hola mundo";
        // return $pecosaId;
        // 1) Buscar el “gancho” Product por la pareja de SILUCIA
        // $product = Product::where('id_order_silucia', $id_order_silucia)
        // $product = ItemPecosa::where('id_order_silucia', $id_order_silucia)->where('id_product_silucia', $id_product_silucia)->firstOrFail(); // 404 si no existe
        $itemPecosa = ItemPecosa::where('id_pecosa_silucia', $pecosaId)->where('id_item_pecosa_silucia', $itemId)->firstOrFail(); // 404 si no existe
        // return $itemPecosa;
        // $itemPecosa = ItemPecosa::where('id_pecosa_silucia', 1)->where('id_item_pecosa_silucia', 42336)->firstOrFail(); // 404 si no existe
// return $itemPecosa;
        // 2) Traer movimientos (puedes paginar si quieres)
        //    Si quieres TODO: ->get();
        //    Si prefieres paginar: ?per_page=20
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

    public function pdfv01(Request $request, $pecosaId, $id_item_pecosa_silucia){


        // no se usaran estos filtros, deberan eliminarse
        // orden -> producto
        // orden ->itemPecosa
        // $pecosa = Product::where('id_order_silucia', $id_order_silucia)->where('id_product_silucia', $id_product_silucia)->firstOrFail();
        // $pecosa = ItemPecosa::where('id_container_silucia', $pecosaId)->where('id_item_pecosa_silucia', $id_item_pecosa_silucia)->firstOrFail();
        $pecosa = ItemPecosa::where('id_pecosa_silucia', $pecosaId)->where('id_item_pecosa_silucia', $id_item_pecosa_silucia)->firstOrFail();
        // Log::info($pecosa);

        // Cargar relaciones con filtros/orden
        $pecosa->load([
            // 'movements' => function ($q) use ($from, $to, $type) {
            'movements' => function ($q) {
                $q->orderBy('movement_date', 'asc')
                ->select([
                    'id','item_pecosa_id','movement_date',
                    'movement_type','amount','observations'
                ]);
            },
            'movements.people' => function ($q) {
                $q->select([
                    'people.dni',
                    'people.full_name',
                    'people.names',
                    'people.first_lastname',
                    'people.second_lastname',
                ])->orderBy('movement_person.attached_at', 'asc');
            },
        ]);

        $movements = $pecosa->movements;
        $totalEntradas = $movements->where('movement_type','entrada')->sum('amount');
        $totalSalidas  = $movements->where('movement_type','salida')->sum('amount');
        $stockFinal    = $totalEntradas - $totalSalidas;

        // ============================
        // REEMPLAZO: DOMPDF -> FPDF
        // ============================

        // 1) Mapear a las filas requeridas por KardexReportPdf
        // $nombre = auth()->user()->name;
        $nombre = Auth::user()->name;
        $rows = [];
        foreach ($movements as $m) {
            $id = $m->id;
            $fecha   = Carbon::parse($m->movement_date)->format('Y-m-d');
            $tipo    = (string)($m->movement_type ?? '');
            $monto   = (float)$m->amount;
            // $persona = optional(  $m->people->first()  )->full_name ?: 'Julia Mamani Yampasi';
            $personaObj = $m->people->first();
            $persona = $personaObj
                ? trim(($personaObj->full_name ?? '') . ' ' . ($personaObj->first_lastname ?? '') . ' ' . ($personaObj->second_lastname ?? ''))
                // : 'Julia Mamani Yampasi';
                : $nombre;
            // $persona = optional($m->people->first())->full_name . optional($m->people->first())->first_lastname?: 'Julia Mamani Yampasi';
            $obs     = (string)($m->observations ?? '');
            $rows[]  = [$id, $fecha, $tipo, $monto, $persona, $obs];
            
        }


        // 2) Texto de introducción (USA lo que tengas en product, con fallback)
        $obra       = (string)($pecosa->desmeta ?? '—');
        $material   = (string)($pecosa->item ?? '—');
        $comprobante= (string)("OC-{$pecosa->id_order_silucia}" ?? "OC-{$pecosaId}");

        // ============================
        // Guardado idéntico a tu flujo
        // ============================
        $dir = 'silucia_product_reports';
        Storage::disk('local')->makeDirectory($dir);

        // 3) QR único por PDF (URL firmada simple)
        // kardex_02874_249069_20250831_021917_10.pdf  ---> id de la orden / id del item / año, mes día /  hora, minuto, segundo / milisegundos
        $base = 'kardex_'. $pecosaId . '_'. $id_item_pecosa_silucia.'_'. now()->format('Ymd_His_'). substr(now()->format('u'), 0, 3);
        $filename = $base . '.pdf';
        $relativePath = "{$dir}/{$filename}";
        // se debe crear el path para que se pueda descargar nuevamente el pdf por un codigo qr

        // $qrCode = UsefulFunctionsForPdfs::generateQRcode($filename);
        $qrCode = UsefulFunctionsForPdfs::generateQRcode(url("/api/files-download?name={$filename}"));

        // 4) Generar PDF con FPDF
        $headers = ['N', 'Fecha', 'Movimiento', 'Monto', 'Recibido / Encargado', 'Observaciones'];
        $widths = [0.1, 0.15, 0.15, 0.15, 0.23, 0.22];
        $styles = [
            'lineHeight' => 4,
            'padX'       => 2,
            'padY'       => 1,
            'aligns'     => ['C','L','L'],
            'border'     => 1,
            'headerFill' => [230,230,230],
        ];


        // antes de que el pdf se cree necesitamos crear el qr e insertarlo
        // http://127.0.0.1:8000/api/files-download?name=kardex_02874_249069_20250829_213601.pdf

        $pdf = new FpdfExample();
        url($filename);
        $pdf->setHeaderLogo($qrCode);
        // $opt = ['rule'=>true];
        // $pdf->renderTitle(opt: ['underline' => true]);
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
        $ok = Storage::disk('local')->put($relativePath, $bytes);
        if (!$ok || !Storage::disk('local')->exists($relativePath)) {
            abort(500, 'No se pudo guardar el PDF.');
        }

        // Contar páginas con FPDI
        $pageCount = 1;
        try {
            $absolute = Storage::disk('local')->path($relativePath);
            $fpdi = new Fpdi();
            $pageCount = (int)$fpdi->setSourceFile($absolute);
        } catch (\Throwable $e) {
            Log::info("No se pudo contar páginas de {$relativePath}: ".$e->getMessage());
        }

        // Registrar reporte y flujo de firma (igual que antes)
        // $report = KardexReport::create([
        //     'product_id'       => $product->id,
        //     'pdf_path'         => $filename, // guardas solo el nombre
        //     'pdf_page_number'  => $pageCount,
        //     'status'           => 'in_progress',
        //     'created_by'       => Auth::id(),
        // ]);
        $report = Report::create([
            'reportable_id'    => $pecosa->id,
            'reportable_type'  => \App\Models\ItemPecosa::class,
            // 'reportable_type'  => \App\Models\Product::class,
            'pdf_path'         => $relativePath,   // << guarda la ruta RELATIVA completa
            'pdf_page_number'  => $pageCount,
            'status'           => 'in_progress',
            'category'         => 'kardex',
            'created_by'       => Auth::id(),
        ]);
        $flow = SignatureFlow::create([
            'report_id' => $report->id,
            'current_step'     => 1,
            'status'           => 'in_progress'
        ]);

        // Ajusta coordenadas si cambiaste orientación P/L
        $roles = config('signing.roles_order', [
            ['role'=>'almacen_almacenero','page'=>1,'pos_x'=>35,        'pos_y'=>745,'width'=>180,'height'=>60],
            ['role'=>'almacen_administrador','page'=>1,'pos_x'=>170,    'pos_y'=>745,'width'=>180,'height'=>60],
            ['role'=>'almacen_residente','page'=>1,'pos_x'=>305,        'pos_y'=>745,'width'=>180,'height'=>60],
            ['role'=>'almacen_supervisor','page'=>1,'pos_x'=>440,       'pos_y'=>745,'width'=>180,'height'=>60],
        ]);
        foreach (array_values($roles) as $i => $role) {
            SignatureStep::create([
                'signature_flow_id' => $flow->id,
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

        SignatureEvent::create([
            'signature_flow_id' => $flow->id,
            'event'   => 'flow_created',
            'user_id' => Auth::id(),
            'meta'    => ['report_id'=>$report->id],
        ]);

        return Storage::download($relativePath, $filename);
    }

    public function pdf(Request $request, ItemPecosa $itemPecosa)
    {

        $pecosa = $itemPecosa;
        $pecosa->load([
            'movements' => function ($q) {
                $q->orderBy('movement_date', 'asc')
                ->select([
                    'id','item_pecosa_id','movement_date',
                    'movement_type','amount','observations'
                ]);
            },
            'movements.people' => function ($q) {
                $q->select([
                    'people.dni',
                    'people.full_name',
                    'people.names',
                    'people.first_lastname',
                    'people.second_lastname',
                ])->orderBy('movement_person.attached_at', 'asc');
            },
        ]);

        $movements = $pecosa->movements;
        $totalEntradas = $movements->where('movement_type','entrada')->sum('amount');
        $totalSalidas  = $movements->where('movement_type','salida')->sum('amount');
        $stockFinal    = $totalEntradas - $totalSalidas;


        /**
         * Guardar en rows[] todas las iflas que iran en el pdf
         */
        $nombre = Auth::user()->name;
        $rows = [];
        foreach ($movements as $m) {
            $id = $m->id;
            $fecha   = Carbon::parse($m->movement_date)->format('Y-m-d');
            $tipo    = (string)($m->movement_type ?? '');
            $monto   = (float)$m->amount;
            $personaObj = $m->people->first();
            $persona = $personaObj
                ? trim(($personaObj->full_name ?? '') . ' ' . ($personaObj->first_lastname ?? '') . ' ' . ($personaObj->second_lastname ?? ''))
                : $nombre;
            $obs     = (string)($m->observations ?? '');
            $rows[]  = [$id, $fecha, $tipo, $monto, $persona, $obs];
        }

        // 2) Texto de introducción (USA lo que tengas en product, con fallback)
        $obra       = (string)($pecosa->desmeta ?? '—');
        $material   = (string)($pecosa->item ?? '—');
        $comprobante= (string)("OC-{$pecosa->cod_meta}" ?? "OC-Indefinido");



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
        $headers = ['N', 'Fecha', 'Movimiento', 'Monto', 'Recibido / Encargado', 'Observaciones'];
        $widths = [0.1, 0.15, 0.15, 0.15, 0.23, 0.22];
        $styles = [
            'lineHeight' => 4,
            'padX'       => 2,
            'padY'       => 1,
            'aligns'     => ['C','L','L'],
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
            'reportable_type'  => ItemPecosa::class,
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

    // funcion incompleta ara crear un pdf de vales de transporte
    public function generatePdfAndFlow(FuelOrder $order)
    {
        // 1) Genera el PDF (usa tu misma clase FPDF/FPDI, QR, etc.)
        $filename     = "fuel_{$order->id}_" . now()->format('Ymd_His') . ".pdf";
        $relativePath = "reports/{$filename}";
        $bytes = 3; 
        Storage::disk('local')->put($relativePath, $bytes);
        // $pageCount = /* contar páginas */;
        $pageCount = 12;

        // 2) Crea Report genérico
        $report = Report::create([
            'reportable_id'   => $order->id,
            'reportable_type' => FuelOrder::class,
            'pdf_path'        => $relativePath,
            'pdf_page_number' => $pageCount,
            'status'          => 'in_progress',
            'category'        => 'fuel_order',
            'created_by'      => Auth::id(),
        ]);

        // 3) Flujo con 3 pasos
        $flow = SignatureFlow::create([
            'report_id'    => $report->id,
            'current_step' => 1,
            'status'       => 'in_progress',
        ]);

        // posiciones de firma (ejemplo en página 1)
        $roles = [
            ['role'=>'fuel_requester','user_id'=>$order->driver_id,    'page'=>1,'pos_x'=>120,'pos_y'=>700,'width'=>180,'height'=>60],
            ['role'=>'fuel_supervisor','user_id'=>$order->supervisor_id,'page'=>1,'pos_x'=>320,'pos_y'=>700,'width'=>180,'height'=>60],
            ['role'=>'fuel_manager',   'user_id'=>$order->manager_id,   'page'=>1,'pos_x'=>520,'pos_y'=>700,'width'=>180,'height'=>60],
        ];

        foreach (array_values($roles) as $i => $r) {
            SignatureStep::create([
                'signature_flow_id' => $flow->id,
                'order'             => $i+1,
                'role'              => $r['role'],
                'user_id'           => $r['user_id'], // fija el firmante si lo sabes
                'page'              => $r['page'],
                'pos_x'             => $r['pos_x'],
                'pos_y'             => $r['pos_y'],
                'width'             => $r['width'],
                'height'            => $r['height'],
                'callback_token'    => Str::random(48),
            ]);
        }

        SignatureEvent::create([
            'signature_flow_id'=>$flow->id,
            'event'=>'flow_created',
            'user_id'=>Auth::id(),
            'meta'=>['report_id'=>$report->id],
        ]);

        // 4) retorna descarga (o JSON)
        return Storage::download($relativePath, $filename);
    }

    public function pdfExampleV0(){

        // Usa la clase global FPDF del paquete setasign/fpdf
        $pdf = new \FPDF('P', 'mm', 'A4');

        // ====== CONFIG GLOBAL ======
        $pdf->SetTitle(mb_convert_encoding('Reporte Kardex', 'ISO-8859-1', 'UTF-8'));
        $pdf->SetAuthor('Gobierno Regiona - Carina');
        // solo acepta tres parametros left, top, right
        $pdf->SetMargins(10, 10, 10);

        // Esta es la funcion para controlar el margen inferior - esto tambien controla los saltos de linea
        // cuando el contenido esta tocando el borde formado desde el margen inferior a 18mm entonces se creara otra pagina
        $pdf->SetAutoPageBreak(true, 18); // margen inferior p/ salto automático  :contentReference[oaicite:2]{index=2}
        
        // no lo tengo muy claro pero parece que nb es una variable que contiene el numero de paginas:
        // 'Página ' . $pdf->PageNo() . '/{nb}'
        $pdf->AliasNbPages();             // habilita {nb} en el footer          :contentReference[oaicite:3]{index=3}

        // ====== HEADER/FOOTER ======
        // FPDF maneja Header/Footer sobreescribiendo métodos; aquí lo simulamos inline
        // creando helpers. Si prefieres la clase extendida, copia estos bloques a una clase PDF extends FPDF. :contentReference[oaicite:4]{index=4}
        $makeHeader = function() use ($pdf) {
            // Logo (si no tienes, comenta esta línea)
            // $pdf->Image(public_path('logo.png'), 15, 10, 20);                 // :contentReference[oaicite:5]{index=5}
            // Título
            $pdf->SetXY(0, 10);        // mueve el cursos a estas coordenadas
            $pdf->SetFont('Arial', 'B', 14);    // configura la fuente como tipo, tamaño    
            $pdf->SetTextColor(30, 30, 30);     // color del texto

            $pdf->SetFillColor(240, 245, 255);  // color de fondo
            $pdf->SetDrawColor(210, 220, 255);  // color de borde de la celda
            $pdf->SetLineWidth(0.2);            // grosor de linea
            // ancho / altura                   / texto                /borde/salto de linea 1 / centraliza / toma estilos definidos en la parte superior 
            $pdf->Cell(0, 7, mb_convert_encoding('REPORTE KARDEX', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C', true);
            $pdf->Ln(2);    // añade un espacio entre la celda anterio con la siguiente
            // Subtítulo en una banda de color
            $pdf->SetFillColor(240, 245, 255);
            $pdf->SetDrawColor(210, 220, 255);
            $pdf->SetLineWidth(0.2);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 8, mb_convert_encoding('Período: 2025-08-01 a 2025-08-31 · Generado por Tu App', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
            $pdf->Ln(2);
        };

        $makeFooter = function() use ($pdf) {
            $pdf->SetY(-15);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $pdf->PageNo() . '/{nb}',        // {nb} se reemplaza con AliasNbPages
                0, 0, 'C'
            );
        };

        // ====== PÁGINA 1 ======
        $pdf->AddPage(); 
        $makeHeader();

        // Marca de agua suave (texto grande gris claro)
        $pdf->SetFont('Arial', 'B', 48);
        $pdf->SetTextColor(240, 240, 240);
        $pdf->SetXY(20, 100);
        $pdf->Cell(0, 20, mb_convert_encoding('CONFIDENCIAL', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // Bloque de “badge” y resumen
        $pdf->SetY(50);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(25, 25, 25);
        $pdf->SetFillColor(230, 248, 240);
        $pdf->SetDrawColor(190, 235, 215);
        $pdf->Cell(0, 10, mb_convert_encoding('Resumen del producto', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L', true);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(60, 60, 60);

        // Dos columnas sencillas
        $leftW = 60; $valW = 20; $rowH = 7;
        $rows = [
            ['Producto', 'Tornillo Hex 3/8"'],
            ['Código',   'TRX-038-AC'],
            ['Unidad',   'cajas'],
            ['Proveedor','FerreMax SAC'],
        ];
        foreach ($rows as [$k, $v]) {
            $pdf->SetFillColor(248, 248, 248);
            $pdf->Cell($leftW, $rowH, mb_convert_encoding($k, 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', true);
            $pdf->Cell($valW,  $rowH, mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
        }
        $pdf->Ln(2);

        // Texto largo justificado (MultiCell)                                         :contentReference[oaicite:6]{index=6}
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(70, 70, 70);
        $lorem = 'Este reporte resume los movimientos de almacén (entradas/salidas) durante el período indicado. ' .
                 'Los datos se presentan en una tabla con totales y observaciones. ' .
                 'El documento incluye numeración de páginas, estilos de color y enlaces de referencia.';
        $pdf->MultiCell(0, 6, mb_convert_encoding($lorem, 'ISO-8859-1', 'UTF-8'), 0, 'J');
        $pdf->Ln(2);

        // ====== TABLA DE MOVIMIENTOS (con zebra stripes) ======
        $headers = ['Fecha', 'Clase', 'Número', 'Tipo', 'Cantidad', 'Observaciones'];
        $w = [25, 20, 28, 20, 25, 72];

        // Cabecera con color                                                            :contentReference[oaicite:7]{index=7}
        $pdf->SetFillColor(230, 235, 255);
        $pdf->SetTextColor(30, 30, 60);
        $pdf->SetDrawColor(200, 205, 235);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Arial', 'B', 10);
        foreach ($headers as $i => $h) {
            $pdf->Cell($w[$i], 8, mb_convert_encoding($h, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Filas mock (simula dataset largo para ver salto de página automático)
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(50, 50, 50);

        $data = [];
        for ($i = 1; $i <= 55; $i++) {
            $data[] = [
                '2025-08-'.str_pad((string)(($i%30)+1), 2, '0', STR_PAD_LEFT),
                'OC',
                '00'.str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                ($i%3===0 ? 'salida' : 'entrada'),
                ($i%3===0 ? 3 : 7),
                'Movimiento n.º '.$i.' — nota breve'
            ];
        }

        $fill = false;
        $totalIn = 0; $totalOut = 0;
        foreach ($data as $row) {
            // zebra (alternando fill)
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 248 : 255, $fill ? 255 : 255);
            $pdf->Cell($w[0], 7, mb_convert_encoding($row[0], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L', $fill);
            $pdf->Cell($w[1], 7, $row[1], 1, 0, 'C', $fill);
            $pdf->Cell($w[2], 7, $row[2], 1, 0, 'C', $fill);
            $pdf->Cell($w[3], 7, mb_convert_encoding($row[3], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', $fill);
            $pdf->Cell($w[4], 7, number_format($row[4]), 1, 0, 'R', $fill);
            $pdf->Cell($w[5], 7, mb_convert_encoding($row[5], 'ISO-8859-1', 'UTF-8'), 1, 1, 'L', $fill);
            $fill = !$fill;

            if ($row[3] === 'entrada') $totalIn += $row[4]; else $totalOut += $row[4];

            // Footer manual por página (opcional)
            if ($pdf->GetY() > 190) { $makeFooter(); }
        }

        // Totales
        $stockFinal = $totalIn - $totalOut;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($w[0]+$w[1]+$w[2]+$w[3], 8, mb_convert_encoding('Totales', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
        $pdf->Cell($w[4], 8, number_format($totalIn).'/'.number_format($totalOut), 1, 0, 'R', true);
        $pdf->Cell($w[5], 8, mb_convert_encoding('Stock final: '.$stockFinal, 'ISO-8859-1', 'UTF-8'), 1, 1, 'L', true);

        // Enlace clicable (a una URL de tu app / ayuda)
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'U', 9);
        $pdf->SetTextColor(40, 80, 200);
        $pdf->Cell(0, 6, 'Ver manual en l\u00ednea', 0, 1, 'L', false, 'https://tu-app/ayuda');

        // Footer final de la última página
        $makeFooter();

        // ====== DESCARGA DIRECTA ======
        $pdf->Output('D', 'reporte_kardex.pdf'); // fuerza descarga  :contentReference[oaicite:8]{index=8}
        return; // no retornes otra Response

    }

    public function pdfExample()
    {

        // pruebas iniciales
        // Crear instancia
        // $pdf = new FpdfExample();
        // $pdf->SetTitle('Ejemplo FPDF');
        // $pdf->SetMargins(0, 0, 0);
        // $pdf->MyBody();
        // $pdf->MyBody();
        // $pdf->MyBody();

        // $pdf->AddPage();
        // $pdf->MyBody();
        // $pdf->SetFont('Arial', '', 12);
        // $pdf->Cell(0, 10, 'Hola mundo desde FPDF', 1, 1, 'C');
        // $pdf->Output();
        // $pdf->insertFinalBox();
        // $this->Cell(0,30,'hola mundo',1,0,'C', true);
        // $pdf->Cell(0, 10, 'Recuadro final en última página', 1, 1, 'C');
        // $pdf->Output('D', 'reporte_kardex.pdf');

        // // size test 
        // $pdf = new FpdfExample();
        // // $pdf->SizeTest();
        // $pdf->SignatureBoxTest();
        // $pdf->Output('D', 'reporte_kardex.pdf');


        // test tabla completa
        
        $headers = ['N', 'Fecha', 'Movimiento', 'Monto', 'Recibido / Encargado', 'Observaciones'];
        $rows = [
            ['1', '2025-08-26','', 'Entrada', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],
            ['1', '2025-08-26','', 'Salida', '500000'],
            ['1', '2025-08-26','', 'Entrada', '100'],
            ['1', '2025-08-26','', 'Salida', '200'],

            // hasta 8 o los que quieras
        ];

        // Anchos en porcentaje (20%, 50%, 30% del ancho útil)
        $widths = [0.05, 0.15, 0.15, 0.15, 0.25, 0.25];
        $pdf = new FpdfExample();
        // $pdf->SizeTest();
        $pdf->drawKardexSummary(
            'Mejoramiento del Colegio Gran Amauta Mejoramiento del Colegio Gran AmautaMejoramiento del Colegio Gran AmautaMejoramiento del Colegio Gran AmautaMejoramiento del Colegio Gran AmautaMejoramiento del Colegio Gran AmautaMejoramiento del Colegio Gran AmautaMejoramiento del Colegio Gran AmautaMejoramiento ',
            'ACERO CORRUGADO GRADO 60 - 3/8 ACERO CORRUGADO GRADO 60 - 3/8ACERO CORRUGADO GRADO 60 - 3/8ACERO CORRUGADO GRADO 60 - 3/8ACERO CORRUGADO GRADO 60 - 3/8ACERO CORRUGADO GRADO 60 - 3/8ACERO CORRUGADO GRADO 60 - 3/8ACERO CORRUGADO GRADO 60 - 3/8"',
            'OC-2025-000123',
            280, 60, '220.00',
            // opcionales:
            ['labelW'=>38, 'lineHeight'=>6, 'padX'=>2, 'padY'=>1]
        );
        $pdf->renderTable($headers, $rows, $widths, [
            'lineHeight' => 6,
            'padX'       => 2,
            'padY'       => 1,
            'aligns'     => ['C','L','L'],
            'border'     => 1,
            'headerFill' => [230,230,230],
        ]);
        // $pdf->drawSignBoxesSimple();
        $pdf->SignatureBoxTest();
        $pdf->Output('D', 'reporte_kardex.pdf');


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




