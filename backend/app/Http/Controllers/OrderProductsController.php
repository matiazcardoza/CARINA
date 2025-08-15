<?php

namespace App\Http\Controllers;

use App\Models\OrderSilucia;
use Illuminate\Http\Request;

class OrderProductsController extends Controller
{

    public function index(OrderSilucia $order_silucia){
        // $orderSilucia = OrderSilucia::find(1);

        // $comments = $video->comments()->latest()->get();
        // return "hola mundo";
        // return response()->json([
        //     'video_id' => $video->id,
        //     'comments' => $comments
        // ]);
        $products = $order_silucia->products()->latest()->get();

        return $products;
        // return response()->json([
        //     'video_id' => $products->id,
        //     'comments' => $comments
        // ]);
    }

    public function store(Request $request, OrderSilucia $order_silucia)
    {
        // return response()->json([
        //     "id_order_silucia" => $orderSilucia->id,
        //     "data_received" => $request->all()
        // ]);
        // Validación
        $data = $request->validate([
            'name'          => ['required','string','max:255'],
            'heritage_code' => ['nullable','string','max:255'],
            'unit_price'    => ['nullable','numeric','min:0','max:99999999.99'],
            'state'         => ['nullable','integer','in:0,1'],
        ]);

        // Crear vía la relación para que se asigne order_id automáticamente
        $product = $order_silucia->products()->create([
            'name'          => $data['name'],
            'heritage_code' => $data['heritage_code'] ?? null,
            'unit_price'    => $data['unit_price'] ?? null,
            'state'         => $data['state'] ?? 1,
        ]);

        return response()->json($product, 201);
    }
}
