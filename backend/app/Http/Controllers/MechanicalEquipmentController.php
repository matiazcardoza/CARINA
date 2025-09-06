<?php

namespace App\Http\Controllers;

use App\Models\MechanicalEquipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MechanicalEquipmentController extends Controller
{
    function index(){
        $mechanicalEquipment = MechanicalEquipment::get();
        return response()->json([
            'message' => 'Equipos cargados correctamente',
            'data' => $mechanicalEquipment
        ]);
    }

    function store(Request $request){
        $newEquipment = MechanicalEquipment::create([
            'machinery_equipment' => $request->machinery_equipment,
            'ability' => $request->ability,
            'brand' => $request->brand,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'year' => $request->year,
            'plate' => $request->plate,
            'cost_hour' => $request->cost_hour,
            'state' => $request->state,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Equipo mecánico creado correctamente.',
            'data' => $newEquipment
        ], 201);
    }

    function update(Request $request, $id){
        $updateEquipment = MechanicalEquipment::find($id);
        $updateEquipment->update([
            'machinery_equipment' => $request->machinery_equipment,
            'ability' => $request->ability,
            'brand' => $request->brand,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            'year' => $request->year,
            'plate' => $request->plate,
            'cost_hour' => $request->cost_hour,
            'state' => $request->state,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equipo actualizado correctamente.',
            'data' => $updateEquipment
        ]);
    }

    public function destroy($id)
    {
        $mechanicalEquipment = MechanicalEquipment::find($id);
        $mechanicalEquipment->delete();

        return response()->json([
            'message' => 'mechanical equipment deleted successfully'
        ], 204);
    }
}
