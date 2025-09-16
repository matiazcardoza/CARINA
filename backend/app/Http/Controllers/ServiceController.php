<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\MechanicalEquipment;
use App\Models\OrderSilucia;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    function index(Request $request)
    {
        $services = Service::select('services.*',
                                        'orders_silucia.supplier',
                                        'orders_silucia.machinery_equipment',
                                        'mechanical_equipment.machinery_equipment as mechanicalEquipment')
                                    ->leftJoin('orders_silucia', 'services.order_id', '=', 'orders_silucia.id')
                                    ->leftJoin('mechanical_equipment', 'services.mechanical_equipment_id', '=', 'mechanical_equipment.id')
                                    ->get();
        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $services
        ]);
    }

    function selectedData(Request $request)
    {
        $services = Service::select('goal_id', 'goal_project', 'goal_detail')
            ->distinct()
            ->get();

        return response()->json([
            'message' => 'Unique goals retrieved successfully',
            'data' => $services
        ]);
    }


    function getDailyPartsData($idGoal)
    {
        $services = Service::where('goal_id', $idGoal)->get();

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

    function liquidarServicio($serviceId){
        $service = Service::find($serviceId);
        $service->update([
            'state_closure' => 2
        ]);

        return response()->json([
            'message' => 'Liquidation service successfully',
            'data' => $service
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
}
