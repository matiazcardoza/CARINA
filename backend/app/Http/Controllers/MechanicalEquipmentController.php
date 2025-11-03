<?php

namespace App\Http\Controllers;

use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\Service;
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
                'services.state_closure',
                'services.goal_id',
                'services.goal_detail',
                'services.goal_project',
                'services.start_date',
                'services.end_date'
            )
            ->where('services.state_closure', '=', 1)
            ->orWhereNull('services.state_closure')
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
                'state_closure' => $equipment->state_closure,
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

    public function supportMachinery(Request $request){
        $service = Service::find($request->service_id);
        $existingOperators = Operator::where('service_id', $request->service_id)->get();

        $existService = Service::where('mechanical_equipment_id', $request->id)
                    ->where('goal_id', $request->goal_id)
                    ->where('state_closure', 2)
                    ->first();

        if($existService){
            $supportService = $existService->update([
                'state_closure' => 1
            ]);
            $service->update([
                'state_closure' => 3
            ]);

        } else{
            $sameService = Service::where('mechanical_equipment_id', $request->id)
                    ->where('goal_id', $request->goal_id)
                    ->where('state_closure', 1)
                    ->first();
            if($sameService){
                return response()->json([
                    'message' => 'No puede apoyar a misma obra'
                ], 409);
            }

            $service->update([
                'state_closure' => 3
            ]);

            $supportService = Service::create([
                'mechanical_equipment_id' => $request->id,
                'goal_id' => $request->goal_id,
                'description' => $request->machinery_equipment . ' ' . $request->brand . ' ' . $request->model . ' ' . $request->plate,
                'goal_project' => $request->goal_project,
                'goal_detail' => $request->goal_detail,
                'start_date' => ($request->start_date === 'NaN-NaN-NaN') ? null : $request->start_date,
                'end_date' => ($request->end_date === 'NaN-NaN-NaN') ? null : $request->end_date,
                'state' => 3
            ]);

            foreach ($existingOperators as $operator) {
                Operator::create([
                    'service_id' => $supportService->id,
                    'name' => $operator->name
                ]);
            }
        }

        return response()->json([
            'message' => 'Service reasigned successfully',
            'data' => $supportService
        ], 201);
    }
}
