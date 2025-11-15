<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\OrderSilucia;
use App\Models\Project;
use App\Models\Service;
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
                   ->select('services.*')
                   ->distinct()
                   ->get();

        $servicesWithTotalTime = $services->map(function ($service) {
            $dailyParts = DailyPart::where('service_id', $service->id)->get();

            $totalSeconds = $dailyParts->reduce(function ($carry, $item) {
                if ($item->time_worked && str_contains($item->time_worked, ':')) {
                    [$hours, $minutes, $seconds] = explode(':', $item->time_worked);
                    $hours = is_numeric($hours) ? (int)$hours : 0;
                    $minutes = is_numeric($minutes) ? (int)$minutes : 0;
                    $seconds = is_numeric($seconds) ? (int)$seconds : 0;
                    return $carry + ($hours * 3600) + ($minutes * 60) + $seconds;
                }
                return $carry;
            }, 0);

            $totalTimeWorked = gmdate('H:i:s', $totalSeconds);

            $service->total_time_worked = $totalTimeWorked;

            return $service;
        });

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $servicesWithTotalTime
        ]);
    }

    public function generateRequest($serviceId)
    {
        $service = Service::find($serviceId);
        $minDate = DailyPart::where('service_id', $serviceId)->min('work_date');
        $maxDate = DailyPart::where('service_id', $serviceId)->max('Work_date');
        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $qr_code = base64_encode("data_qr_example");
        $data = [
            'logoPath' => $logoPath,
            'service' => $service,
            'minDate' => $minDate,
            'maxDate' => $maxDate,
            'pdf' => true,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.request_machinery', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('anexo_01_planilla.pdf');
    }

    public function generateAuth($serviceId)
    {
        $service = Service::find($serviceId);
        $dateRange = DailyPart::where('service_id', $serviceId)
            ->selectRaw('MIN(work_date) as min_date, MAX(work_date) as max_date')
            ->first();

        $minDate = $dateRange->min_date;
        $maxDate = $dateRange->max_date;

        Carbon::setLocale('es');
        $minDateFormatted = Carbon::parse($minDate)->locale('es')->isoFormat('D/M/YYYY');
        $maxDateFormatted = Carbon::parse($maxDate)->locale('es')->isoFormat('D/M/YYYY');

        $mechanicalEquipment = MechanicalEquipment::find($service->mechanical_equipment_id);
        $orderSilucia = OrderSilucia::find($service->order_id);

        $dailyParts = DailyPart::where('service_id', $serviceId)->get();

        if ($dailyParts->isEmpty()) {
            return response()->json(['error' => 'No se encontraron datos para este servicio'], 404);
        }

        $startDate = Carbon::parse($minDate);
        $endDate = Carbon::parse($maxDate);
        $dateRange = [];

        while ($startDate->lte($endDate)) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        $processedData = [];
        $totalHoursWorked = 0;
        $totalEquivalentHours = 0;
        $totalFuelConsumption = 0;
        $totalDaysWorked = 0;
        $totalAmount = 0;

        foreach ($dateRange as $date) {
            $dayParts = $dailyParts->where('work_date', $date);

            if ($dayParts->count() > 0) {
                $totalSecondsWorked = 0;
                $totalFuelDay = 0;

                foreach ($dayParts as $part) {
                    $timeParts = explode(':', $part->time_worked);
                    $hours = (int)$timeParts[0];
                    $minutes = (int)$timeParts[1];
                    $seconds = isset($timeParts[2]) ? (int)$timeParts[2] : 0;

                    $totalSecondsWorked += ($hours * 3600) + ($minutes * 60) + $seconds;
                    $totalFuelDay += $part->initial_fuel ?? 0;
                }

                $hoursWorked = floor($totalSecondsWorked / 3600);
                $minutesWorked = floor(($totalSecondsWorked % 3600) / 60);
                $timeWorkedFormatted = sprintf('%02d:%02d', $hoursWorked, $minutesWorked);

                $equivalentHours = $hoursWorked + ($minutesWorked / 60);
                $costPerHour = $mechanicalEquipment->cost_hour ?? $orderSilucia->cost_hour;
                $dailyAmount = $equivalentHours * $costPerHour;

                $processedData[] = [
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'time_worked' => $timeWorkedFormatted,
                    'equivalent_hours' => $equivalentHours,
                    'fuel_consumption' => $totalFuelDay,
                    'days_worked' => '1.00',
                    'cost_per_hour' => $costPerHour,
                    'total_amount' => $dailyAmount,
                    'has_work' => true
                ];
                $totalHoursWorked += $totalSecondsWorked;
                $totalEquivalentHours += $equivalentHours;
                $totalFuelConsumption += $totalFuelDay;
                $totalDaysWorked++;
                $totalAmount += $dailyAmount;

            } else {
                $costPerHour = $mechanicalEquipment->cost_hour ?? $orderSilucia->cost_hour;
                $processedData[] = [
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'time_worked' => '-',
                    'equivalent_hours' => '-',
                    'fuel_consumption' => '-',
                    'days_worked' => '-',
                    'cost_per_hour' => $costPerHour,
                    'total_amount' => 0,
                    'has_work' => false
                ];
            }
        }

        // Convertir total de horas trabajadas a formato HH:MM
        $totalHours = floor($totalHoursWorked / 3600);
        $totalMinutes = floor(($totalHoursWorked % 3600) / 60);
        $totalTimeFormatted = sprintf('%02d:%02d', $totalHours, $totalMinutes);

        // Preparar datos de totales sin redondear internamente
        $totals = [
            'time_worked' => $totalTimeFormatted,
            'equivalent_hours' => $totalEquivalentHours,
            'fuel_consumption' => $totalFuelConsumption,
            'days_worked' => $totalDaysWorked,
            'cost_per_hour' => $mechanicalEquipment->cost_hour ?? $orderSilucia->cost_hour ?? 285.00,
            'total_amount' => $totalAmount
        ];

        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $qr_code = base64_encode("data_qr_example");

        $data = [
            'logoPath' => $logoPath,
            'mechanicalEquipment' => $mechanicalEquipment,
            'orderSilucia' => $orderSilucia,
            'minDate' => $minDateFormatted,
            'maxDate' => $maxDateFormatted,
            'service' => $service,
            'processedData' => $processedData,
            'totals' => $totals,
            'pdf' => true,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.report_auth', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('anexo_01_planilla.pdf');
    }

    public function generateLiquidation($serviceId)
    {
        $service = Service::find($serviceId);
        $dateRange = DailyPart::where('service_id', $serviceId)
            ->selectRaw('MIN(work_date) as min_date, MAX(work_date) as max_date')
            ->first();

        $minDate = $dateRange->min_date;
        $maxDate = $dateRange->max_date;

        Carbon::setLocale('es');
        $minDateFormatted = Carbon::parse($minDate)->locale('es')->isoFormat('DD/MM/YYYY');
        $maxDateFormatted = Carbon::parse($maxDate)->locale('es')->isoFormat('DD/MM/YYYY');

        $mechanicalEquipment = MechanicalEquipment::find($service->mechanical_equipment_id);
        $orderSilucia = OrderSilucia::find($service->order_id);

        $dailyParts = DailyPart::where('service_id', $serviceId)->get();

        if ($dailyParts->isEmpty()) {
            return response()->json(['error' => 'No se encontraron datos para este servicio'], 404);
        }

        $totalHoursWorked = 0;
        $totalEquivalentHours = 0;
        $totalFuelConsumption = 0;
        $totalDaysWorked = 0;
        $totalAmount = 0;

        $startDate = Carbon::parse($minDate);
        $endDate = Carbon::parse($maxDate);
        $dateRange = [];

        while ($startDate->lte($endDate)) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        // Procesar cada día
        foreach ($dateRange as $date) {
            $dayParts = $dailyParts->where('work_date', $date);

            if ($dayParts->count() > 0) {
                $totalSecondsWorked = 0;
                $totalFuelDay = 0;

                foreach ($dayParts as $part) {
                    $timeParts = explode(':', $part->time_worked);
                    $hours = (int)$timeParts[0];
                    $minutes = (int)$timeParts[1];
                    $seconds = isset($timeParts[2]) ? (int)$timeParts[2] : 0;

                    $totalSecondsWorked += ($hours * 3600) + ($minutes * 60) + $seconds;
                    $totalFuelDay += $part->initial_fuel ?? 0;
                }

                if ($totalSecondsWorked > 0) {
                    $totalHoursWorked += $totalSecondsWorked;
                    $equivalentHours = $totalSecondsWorked / 3600;
                    $totalEquivalentHours += $equivalentHours;
                    $totalFuelConsumption += $totalFuelDay;
                    $totalDaysWorked++;

                    // Calcular costo del día
                    $costPerHour = $mechanicalEquipment->cost_hour ?? $orderSilucia->cost_hour ?? 285.00;
                    $dailyAmount = $equivalentHours * $costPerHour;
                    $totalAmount += $dailyAmount;
                }
            }
        }

        // Convertir total de horas trabajadas a formato legible
        $totalHours = floor($totalHoursWorked / 3600);
        $totalMinutes = floor(($totalHoursWorked % 3600) / 60);
        $totalTimeFormatted = sprintf('%02d:%02d', $totalHours, $totalMinutes);

        $costPerHour = $mechanicalEquipment->cost_hour ?? $orderSilucia->cost_hour ?? 285.00;
        $costPerDay = $totalDaysWorked > 0 ? $totalAmount / $totalDaysWorked : 0;

        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $totalInWords = strtoupper($formatter->format(floor($totalAmount)));
        $cents = round(($totalAmount - floor($totalAmount)) * 100);
        $totalInWordsComplete = $totalInWords . ' CON ' . sprintf('%02d', $cents) . '/100 SOLES';

        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $logoWorkPath = storage_path('app/public/image_pdf_template/logo_work.png');
        $qr_code = base64_encode("data_qr_example");

        $data = [
            'logoPath' => $logoPath,
            'logoWorkPath' => $logoWorkPath,
            'service' => $service,
            'minDate' => $minDateFormatted,
            'maxDate' => $maxDateFormatted,
            'dailyPart' => $dailyParts,
            'pdf' => true,
            'qr_code' => $qr_code,
            'totalDaysWorked' => $totalDaysWorked,
            'totalEquivalentHours' => $totalEquivalentHours,
            'totalTimeFormatted' => $totalTimeFormatted,
            'costPerHour' => $costPerHour,
            'costPerDay' => $costPerDay,
            'totalAmount' => $totalAmount,
            'totalInWords' => $totalInWordsComplete
        ];

        $pdf = Pdf::loadView('pdf.liquidation_service', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('liquidacion_servicio_alquiler.pdf');
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
