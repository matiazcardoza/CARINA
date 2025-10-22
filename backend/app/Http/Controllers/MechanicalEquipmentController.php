<?php

namespace App\Http\Controllers;

use App\Models\MechanicalEquipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MechanicalEquipmentController extends Controller
{
    function index(){
        $mechanicalEquipment = DB::table('mechanical_equipment')
            ->select(
                'mechanical_equipment.*',
                'services.id as service_id',
                'services.state as state_service',
                'services.goal_id',
                'services.goal_detail',
                'services.goal_project',
                'services.start_date',
                'services.end_date'
            )
            ->where('services.state_closure', '!=', 2)
            ->leftJoin('services', 'mechanical_equipment.id', '=', 'services.mechanical_equipment_id')
            ->get();

        $serviceIds = $mechanicalEquipment->pluck('service_id')->filter()->unique()->toArray();
        
        $operators = DB::table('operators')
            ->whereIn('service_id', $serviceIds)
            ->where('state', 1)
            ->get()
            ->groupBy('service_id');

        $result = $mechanicalEquipment->map(function($equipment) use ($operators) {
            return [
                'id' => $equipment->id,
                'service_id' => $equipment->service_id,
                'machinery_equipment' => $equipment->machinery_equipment,
                'ability' => $equipment->ability,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'plate' => $equipment->plate,
                'year' => $equipment->year,
                'serial_number' => $equipment->serial_number,
                'state' => $equipment->state,
                'state_service' => $equipment->state_service,
                'goal_id' => $equipment->goal_id,
                'goal_detail' => $equipment->goal_detail,
                'goal_project' => $equipment->goal_project,
                'start_date' => $equipment->start_date,
                'end_date' => $equipment->end_date,
                'operators' => $equipment->service_id && isset($operators[$equipment->service_id])
                ? $operators[$equipment->service_id]->map(function($op) {
                    return [
                        'id' => $op->id,
                        'name' => $op->name
                    ];
                })->toArray()
                : []
            ];
        });

        return response()->json([
            'message' => 'Equipos cargados correctamente',
            'data' => $result
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

    function update(Request $request){
        $updateEquipment = MechanicalEquipment::find($request->id);
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
