<?php

namespace App\Http\Controllers;

use App\Models\EquipmentOrder;
use App\Models\MechanicalEquipment;
use Illuminate\Http\Request;

class ApiResponseController extends Controller
{
    public function consultEquipment(string $plate)
    {
        $equipment = MechanicalEquipment::where('plate', $plate)->first()
            ?? EquipmentOrder::where('plate', $plate)->first();

        if (!$equipment) {
            return response()->json([
                'message' => 'Equipment not found',
                'data' => null
            ], 404);
        }

        $equipmentSend = [
            'machinery_equipment' => $equipment->machinery_equipment,
            'ability' => $equipment->ability,
            'brand' => $equipment->brand,
            'model' => $equipment->model,
            'serial_number' => $equipment->serial_number,
            'year' => $equipment->year,
            'plate' => $equipment->plate,
        ];

        return response()->json([
            'message' => 'Get equipment completed successfully',
            'data' => $equipmentSend
        ], 200);
    }
}
