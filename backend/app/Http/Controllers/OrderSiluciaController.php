<?php

namespace App\Http\Controllers;

use App\Models\EquipmentOrder;
use App\Models\Operator;
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
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();
        $exists = Project::where('user_id', Auth::id())
                ->where('goal_id', $request->order['idmeta'] ?? $request->meta_id)
                ->exists();
        if (!$exists && !$usuario->hasRole(['SuperAdministrador_pd', 'Admin_equipoMecanico_pd'])) {
            return response()->json([
                'success' => false,
                'message' => 'La meta seleccionada no pertenece al proyecto del usuario autenticado.'
            ], 403);
        }
        if($request->maquinaria_id){
            $newService = Service::create([
                'mechanical_equipment_id' => $request->maquinaria_id,
                'goal_id' => $request->meta_id,
                'description' => $request->maquinaria_equipo . ' ' . $request->maquinaria_marca . ' ' . $request->maquinaria_modelo . ' ' . $request->maquinaria_placa,
                'goal_project' => $request->meta_codigo,
                'goal_detail' => $request->meta_descripcion,
                'start_date' => ($request->start_date === 'NaN-NaN-NaN') ? null : $request->start_date,
                'end_date' => ($request->end_date === 'NaN-NaN-NaN') ? null : $request->end_date,
                'state' => 3
            ]);
            $operatorsArray = json_decode($request->operators, true);
            $createdOperators = [];

            foreach ($operatorsArray as $operatorData) {
                if (!empty(trim($operatorData['name'] ?? ''))) {
                    $operator = Operator::create([
                        'service_id' => $newService->id,
                        'name' => trim($operatorData['name']),
                    ]);
                    $createdOperators[] = $operator;
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'registro importado correctamente.',
                'servicio' => $newService
            ], 201);
        } else {
            $exists = OrderSilucia::where('silucia_id', $request->order['idservicio'])->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un registro con este silucia_id: ' . $request->order['idservicio']
                ], 409);
            }

            $newOrderSilucia = OrderSilucia::create([
                'silucia_id' => $request->order['idservicio'],
                'order_type' => 'SERVICIO',
                'supplier' => $request->order['rsocial'],
                'ruc_supplier' => $request->order['ruc'],
                'delivery_date' => $request->order['fechaPrestacion'],
                'deadline_day' => $request->order['plazoPrestacion'],
            ]);

            $services = [];

            foreach ($request->items as $equipment) {
                $newEquipment = EquipmentOrder::create([
                    'order_silucia_id' => $newOrderSilucia->id,
                    'machinery_equipment' => $equipment['machinery_equipment'],
                    'ability' => $equipment['ability'],
                    'brand' => $equipment['brand'],
                    'model' => $equipment['model'],
                    'serial_number' => $equipment['serial_number'],
                    'year' => $equipment['year'],
                    'plate' => $equipment['plate'],
                ]);

                $newService = Service::create([
                    'order_id' => $newOrderSilucia->id,
                    'goal_id' => $request->order['idmeta'],
                    'medida_id' => $equipment['medida_id'],
                    'description' => $equipment['machinery_equipment'] . ' ' . $equipment['brand'] . ' ' . $equipment['model'] . ' ' . $equipment['plate'],
                    'goal_project' => $request->order['cod_meta'],
                    'goal_detail' => $request->order['desmeta'],
                    'start_date' => $request->order['fechaPrestacion'],
                    'end_date' => $request->order['fechaFinal'],
                    'state' => $equipment['tipoMaquinaria']
                ]);

                foreach ($equipment['operators'] as $operator) {
                    Operator::create([
                        'service_id' => $newService->id,
                        'name' => $operator['operatorName'],
                    ]);
                }

                $services[] = $newService;
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro importado correctamente.',
                'order_silucia' => $newOrderSilucia,
                'servicios' => $services
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
