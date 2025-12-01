<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\OrderSilucia;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceLiquidationAdjustment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    function index(Request $request)
    {
        $goalIds = Project::Where('user_id', Auth::id())->pluck('goal_id');
        $services = DB::table('services')
            ->select(
                'services.id',
                'services.goal_id',
                'services.description',
                'services.goal_project',
                'services.goal_detail',
                'services.start_date',
                'services.end_date',
                'services.state',
                'orders_silucia.supplier',
                DB::raw('GROUP_CONCAT(DISTINCT equipment_order.machinery_equipment) as machinery_equipment'),
                'mechanical_equipment.machinery_equipment as mechanicalEquipment'
            )
            ->leftJoin('orders_silucia', 'services.order_id', '=', 'orders_silucia.id')
            ->leftJoin('equipment_order', 'orders_silucia.id', '=', 'equipment_order.order_silucia_id')
            ->leftJoin('mechanical_equipment', 'services.mechanical_equipment_id', '=', 'mechanical_equipment.id')
            ->when(Auth::id() != 1, function ($query) use ($goalIds) {
                $query->whereIn('services.goal_id', $goalIds);
            })
            ->where('services.state_closure', '=', 1)
            ->groupBy(
                'services.id',
                'services.goal_id',
                'services.description',
                'services.goal_project',
                'services.goal_detail',
                'services.start_date',
                'services.end_date',
                'services.state',
                'orders_silucia.supplier',
                'mechanicalEquipment'
            )
            ->orderBy('services.id', 'asc')
            ->get();
        foreach ($services as $service) {
            $operators = DB::table('operators')
                ->where('service_id', $service->id)
                ->where('state', 1)
                ->select('id', 'name')
                ->get();
            $service->operators = $operators;
        }
        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $services
        ]);
    }

    public function selectedData(Request $request)
    {
        /** @var \App\Models\User $usuario */
        $usuario = Auth::user();
        if ($usuario->hasRole(['SuperAdministrador_pd', 'Admin_equipoMecanico_pd'])) {
            $services = Service::select('goal_id', 'goal_project', 'goal_detail')
                ->distinct()
                ->get();
            return response()->json([
                'message' => 'Unique goals retrieved successfully (all projects)',
                'data' => $services
            ]);
        }
        $goalIds = Project::where('user_id', $usuario->id)->pluck('goal_id');
        if ($goalIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene proyectos asignados.'
            ], 403);
        }
        $services = Service::select('goal_id', 'goal_project', 'goal_detail')
            ->whereIn('goal_id', $goalIds)
            ->distinct()
            ->get();
        return response()->json([
            'message' => 'Unique goals retrieved successfully (user projects only)',
            'data' => $services
        ]);
    }

    function getDailyPartsData($idGoal)
{
    $services = Service::join('daily_parts', 'services.id', '=', 'daily_parts.service_id')
               ->where('services.goal_id', $idGoal)
               ->where('services.state_valorized', '!=', 2)
               ->select('services.*')
               ->distinct()
               ->get();

    $machinery = [];
    $totalValorationAmount = 0;

    foreach ($services as $service) {
        // Obtener daily parts del servicio
        if ($service->state != 3) {
            continue;
        }
        $dailyParts = DailyPart::where('service_id', $service->id)->get();

        // Calcular segundos totales trabajados
        $totalSecondsWorked = $dailyParts->reduce(function ($carry, $item) {
            if ($item->time_worked && str_contains($item->time_worked, ':')) {
                [$hours, $minutes, $seconds] = array_pad(explode(':', $item->time_worked), 3, 0);
                $hours = is_numeric($hours) ? (int)$hours : 0;
                $minutes = is_numeric($minutes) ? (int)$minutes : 0;
                $seconds = is_numeric($seconds) ? (int)$seconds : 0;
                return $carry + ($hours * 3600) + ($minutes * 60) + $seconds;
            }
            return $carry;
        }, 0);

        // Formatear tiempo total trabajado
        $totalHours = floor($totalSecondsWorked / 3600);
        $totalMinutes = floor(($totalSecondsWorked % 3600) / 60);
        $totalTimeFormatted = sprintf('%02d:%02d', $totalHours, $totalMinutes);

        // Calcular horas equivalentes
        $totalEquivalentHours = $totalHours + ($totalMinutes / 60);

        // Calcular días trabajados (días únicos con registros)
        $totalDaysWorked = $dailyParts->pluck('work_date')->unique()->count();

        // Verificar si hay ajuste para este servicio
        $adjustment = ServiceLiquidationAdjustment::where('service_id', $service->id)->first();

        if ($adjustment) {
            // Si hay ajuste, usar datos ajustados
            $adjustedData = json_decode($adjustment->adjusted_data, true);
            $equipment = (object) $adjustedData['equipment'];
            $costPerHour = $adjustedData['auth']['totals']['cost_per_hour'] ?? 0;
            $totalAmount = $adjustedData['auth']['totals']['total_amount'] ?? 0;
            $costPerDay = $adjustedData['liquidation']['cost_per_day'] ?? 0;
        } else {
            // Si no hay ajuste, calcular normalmente
            $equipment = MechanicalEquipment::find($service->mechanical_equipment_id);
            $operators = Operator::where('service_id', $service->id)->get();
            $equipment->operators = $operators;
            $costPerHour = $equipment->cost_hour ?? 0;
            $totalAmount = $totalEquivalentHours * $costPerHour;
            $costPerDay = $totalDaysWorked > 0 ? $totalAmount / $totalDaysWorked : 0;
        }

        // Agregar datos de esta maquinaria
        $machinery[] = [
            'service_id' => $service->id,
            'equipment' => $equipment,
            'time_worked' => $totalTimeFormatted,
            'equivalent_hours' => round($totalEquivalentHours, 2),
            'cost_per_hour' => $costPerHour,
            'total_amount' => round($totalAmount, 2),
            'cost_per_day' => round($costPerDay, 2),
            'days_worked' => $totalDaysWorked,
        ];

        // Sumar al total general
        $totalValorationAmount += $totalAmount;
    }

    $valoration = [
        'machinery' => $machinery,
        'valoration_amount' => round($totalValorationAmount, 2)
    ];

    return response()->json([
        'message' => 'Daily work log retrieved successfully',
        'valoration' => $valoration,
        'data' => $services
    ]);
}

    public function getPathPdf($id)
    {
        $service = Service::find($id);
        return response()->json([
            'message' => 'Service retrieved successfully',
            'data' => $service
        ]);
    }

    public function getIdmeta($mechanicalId)
    {
        $service = Service::where('mechanical_equipment_id', $mechanicalId)->get();
        return response()->json([
            'message' => 'Service retrieved successfully',
            'data' => $service
        ]);
    }

    public function updateIdmeta(Request $request)
    {
        $servicio = Service::find($request->service_id);
        if($servicio->goal_id === $request->goal_id){

            Log::info('ingreso a funcion si es el mismo goal_id');
            $Service = $servicio->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            $operatorsArray = $request->operators;
            $incomingIds = collect($operatorsArray)
                ->pluck('id')
                ->filter()
                ->toArray();
            $currentOperators = Operator::where('service_id', $servicio->id)->get();
            $toInactivate = $currentOperators->whereNotIn('id', $incomingIds);
            foreach ($toInactivate as $op) {
                $op->update(['state' => 0]);
            }
            foreach ($operatorsArray as $opData) {
                $name = trim($opData['name']);
                if (!empty($opData['id'])) {
                    $operator = Operator::find($opData['id']);
                    if ($operator && $operator->name !== $name) {
                        $operator->update(['name' => $name]);
                    }
                } else {
                    Operator::create([
                        'service_id' => $servicio->id,
                        'name' => $name
                    ]);
                }
            }
        }else{
            $existServiceState3 = Service::where('mechanical_equipment_id', $request->id)
                        ->where('goal_id', $request->goal_id)
                        ->where('state_closure', '=', 3)->first();
            $existServiceSupport = Service::where('mechanical_equipment_id', $request->id)
                        ->where('goal_id', $request->goal_id)
                        ->where('state_closure', '=', 2)->first();
            if($existServiceState3){
                $existServiceState3->update([
                    'state_closure' => 1
                ]);

                $servicio->update([
                    'state_closure' => 2
                ]);

                return response()->json([
                    'message' => 'Service reasigned successfully',
                    'data' => $servicio
                ]);
            }else {
                if($existServiceSupport){
                    $existServiceSupport->update([
                        'state_closure' => 1
                    ]);

                    $servicio->update([
                        'state_closure' => 2
                    ]);

                    return response()->json([
                        'message' => 'Service reasigned successfully',
                        'data' => $servicio
                    ]);
                } else{
                    $existService = Service::where('mechanical_equipment_id', $request->id)
                                ->where('goal_id', $request->goal_id)->first();
                    if($existService){
                        return response()->json([
                            'message' => 'Esta maquinaria esta de apoyo, no puede reasignar hasta regresar a su meta original'
                        ], 409);
                    }

                    $servicio->update([
                        'state_closure' => 2
                    ]);
                    $Service = Service::create([
                        'mechanical_equipment_id' => $request->id,
                        'goal_id' => $request->goal_id,
                        'description' => $request->machinery_equipment . ' ' . $request->brand . ' ' . $request->model . ' ' . $request->plate,
                        'goal_project' => $request->goal_project,
                        'goal_detail' => $request->goal_detail,
                        'start_date' => ($request->start_date === 'NaN-NaN-NaN') ? null : $request->start_date,
                        'end_date' => ($request->end_date === 'NaN-NaN-NaN') ? null : $request->end_date,
                        'state' => 3
                    ]);

                    $createdOperators = [];
                    foreach ($request->operators as $operatorData) {
                        if (is_object($operatorData)) {
                            $operatorData = (array) $operatorData;
                        }
                        $name = trim($operatorData['name'] ?? '');
                        if (!empty($name)) {
                            $operator = Operator::create([
                                'service_id' => $Service->id,
                                'name' => $name,
                            ]);
                            $createdOperators[] = $operator;
                        }
                    }
                }
            }
        }
        return response()->json([
            'message' => 'Service reasigned successfully',
            'data' => $Service
        ]);
    }
}
