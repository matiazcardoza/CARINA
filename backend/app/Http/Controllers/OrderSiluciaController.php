<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\OrderSilucia;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderSiluciaController extends Controller
{
    public function importOrder(Request $request)
    {
        $exists = Project::where('user_id', Auth::id())
                ->where('goal_id', $request->idmeta)
                ->exists();
        if(!$exists){
            return response()->json([
                'success' => false,
                'message' => 'La meta seleccionada no pertenece al proyecto del usuario autenticado.'
            ], 400);

        }
        if($request->maquinaria_id){
            $newService = Service::create([
                'mechanical_equipment_id' => $request->maquinaria_id,
                'goal_id' => $request->meta_id,
                'operator' => $request->operador,
                'description' => $request->maquinaria_equipo . ' ' . $request->maquinaria_marca . ' ' . $request->maquinaria_modelo . ' ' . $request->maquinaria_placa,
                'goal_project' => $request->meta_codigo,
                'goal_detail' => $request->meta_descripcion,
                'start_date' => ($request->start_date === 'NaN-NaN-NaN') ? null : $request->start_date,
                'end_date' => ($request->end_date === 'NaN-NaN-NaN') ? null : $request->end_date,
                'state' => 3
            ]);

            return response()->json([
                'success' => true,
                'message' => 'registro importado correctamente.',
                'servicio' => $newService
            ], 201);
        } else{
            $newOrderSilucia = OrderSilucia::create([
                'silucia_id' => $request->idservicio,
                'order_type' => 'Servicio',
                'supplier' => $request->rsocial,
                'ruc_supplier' => $request->ruc,
                'machinery_equipment' => $request->maquinaria,
                'ability' => $request->capacidad,
                'brand' => $request->marca,
                'model' => $request->modelo,
                'serial_number' => $request->serie,
                'year' => $request->year,
                'plate' => $request->placa,
                'delivery_date' => $request->fechaPrestacion,
                'deadline_day' => $request->plazoPrestacion
            ]);

            $newService = Service::create([
                'order_id' => $newOrderSilucia->id,
                'goal_id' => $request->idmeta,
                'operator' => $request->operador,
                'description' => $request->description . ' ' . $request->placa,
                'goal_project' => $request->cod_meta,
                'goal_detail' => $request->desmeta,
                'start_date' => $request->fechaPrestacion,
                'end_date' => $request->fechaFinal,
                'state' => $request->tipoMaquinaria
            ]);

            return response()->json([
                'success' => true,
                'message' => 'registro importado correctamente.',
                'order_silucia' => $newOrderSilucia,
                'servicio' => $newService
            ], 201);
        }
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
