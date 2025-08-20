<?php

namespace App\Http\Controllers;

use App\Models\MovementKardex;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MovementKardexController extends Controller
{
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


}
