<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\OrderSilucia;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class OrderSiluciaController extends Controller
{
    function index(Request $request)
    {
        $orderSilucia = OrderSilucia::select('orders_silucia.*', 'services.state', 'services.description')
                                    ->leftjoin('services', 'orders_silucia.id', '=', 'services.order_id')
                                    ->get();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $orderSilucia
        ]);
    }

    public function importOrder(Request $request)
    {
        $silucia_id = $request->idservicio;
        $goal_project = $request->idmeta;
        $api_date = $request->item;
        $goal_detail = $request->desmeta;
        $state = $request->state;
        $description = $request->item;
        $symbol = ',';
        $clean_description = strstr($description, $symbol, true);

        $order = new OrderSilucia();
        $order->silucia_id = $silucia_id;
        $order->order_type = 'SERVICIO';
        $order->issue_date = now();
        $order->goal_project = $goal_project;
        $order->goal_detail = $goal_detail;
        $order->api_date = $api_date;

        
        $order->save();

        $service = new Service();
        $service->order_id = $order->id;
        $service->description = $clean_description;
        $service->state = $state;
        $service->save();
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
