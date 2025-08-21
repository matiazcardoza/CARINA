<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementRequest;
use App\Models\MovementKardex;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
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
        return $request;
        $data = [
            [
                'example'=>'example'
            ]
        ];
        // $pdf = Pdf::loadView('pdfKardex.reporte', compact('items'))->setPaper('a4', 'portrait');
        $pdf = Pdf::loadView('pdfKardex.reporte', compact('items'))->setPaper('a4', 'portrait');
        $pdf = Pdf::loadView('pdfKardex.tareo', compact('items'))->setPaper('a4', 'portrait');
        return $pdf->download("orden.pdf");
    }


}
