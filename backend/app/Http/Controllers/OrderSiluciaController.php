<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\OrderSilucia;

class OrderSiluciaController extends Controller
{
    public function index()
    {
        $orders = OrderSilucia::all();
        return response()->json($orders);
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
