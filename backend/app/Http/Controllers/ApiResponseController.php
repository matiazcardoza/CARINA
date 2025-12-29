<?php

namespace App\Http\Controllers;

use App\Models\EquipmentOrder;
use App\Models\MechanicalEquipment;
use Illuminate\Http\Request;

class ApiResponseController extends Controller
{
    public function consultEquipment($plate){
        $equipment = MechanicalEquipment::where('plate', $plate)->first();

        if($equipment){
            $equipmentSend = [
                'machinery_equipment' => $equipment->machinery_equipment,
                'ability' => $equipment->ability,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'serial_number' => $equipment->serial_number,
                'year' => $equipment->year,
                'plate' => $equipment->plate
            ];
        }else{
            $equipmentOrder = EquipmentOrder::where('plate', $plate)->first();
            $equipmentSend = [
                'machinery_equipment' => $equipmentOrder->machinery_equipment,
                'ability' => $equipmentOrder->ability,
                'brand' => $equipmentOrder->brand,
                'model' => $equipmentOrder->model,
                'serial_number' => $equipmentOrder->serial_number,
                'year' => $equipmentOrder->year,
                'plate' => $equipmentOrder->plate
            ];
        }

        return response()->json([
            'message' => 'get equipment completed successfully',
            'data' => $equipmentSend
        ], 201);
    }
}
