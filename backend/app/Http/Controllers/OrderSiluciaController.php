<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\OrderSilucia;

class OrderSiluciaController extends Controller
{
    function index(Request $request)
    {
        $orderSilucia = OrderSilucia::select('orders_silucia.*', 'services.state')
                                    ->leftjoin('services', 'orders_silucia.id', '=', 'services.order_id')
                                    ->get();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $orderSilucia
        ]);
    }

    // VideoCommentController
    // public function OrderProductsController(Video $video){
    public function OrderProductsController(OrderSilucia $orderSilucia){
        // $orderSilucia = OrderSilucia::find(1);

        // $comments = $video->comments()->latest()->get();
        // return "hola mundo";
        // return response()->json([
        //     'video_id' => $video->id,
        //     'comments' => $comments
        // ]);
        $products = $orderSilucia->products()->latest()->get();

        return $products;
        // return response()->json([
        //     'video_id' => $products->id,
        //     'comments' => $comments
        // ]);
    }


}
