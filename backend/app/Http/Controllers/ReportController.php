<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function getLiquidationData($serviceId)
    {
        //eqwuipment data
        $service = Service::find($serviceId);
        $equipment = MechanicalEquipment::find($service->mechanical_equipment_id);
        $operators = Operator::where('service_id', $serviceId)->get();
        $equipment->operators = $operators;

        //request data
        $minDate = DailyPart::where('service_id', $serviceId)->min('work_date');
        $maxDate = DailyPart::where('service_id', $serviceId)->max('Work_date');
        $minStartTime = DailyPart::where('service_id', $serviceId)->min('start_time');
        $maxEndTime = DailyPart::where('service_id', $serviceId)->max('end_time');
        $request = [
            'goal_detail' => $service->goal_detail,
            'minDate'     => $minDate,
            'maxDate'     => $maxDate,
            'minStartTime'=> $minStartTime,
            'maxEndTime'  => $maxEndTime,
        ];

        //auth data
        $minDateFormatted = Carbon::parse($minDate)->locale('es')->isoFormat('D/M/YYYY');
        $maxDateFormatted = Carbon::parse($maxDate)->locale('es')->isoFormat('D/M/YYYY');
        $startDate = Carbon::parse($minDate);
        $endDate = Carbon::parse($maxDate);

        $dateRange = [];
        while ($startDate->lte($endDate)) {
            $dateRange[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        $dailyParts = DailyPart::where('service_id', $serviceId)->get();
        $totalSecondsWorked = 0;
        $totalEquivalentHours = 0;
        $totalFuelConsumption = 0;
        $totalDaysWorked = 0;
        $totalAmount = 0;
        $costPerHour = $equipment->cost_hour;

        $processedData = [];

        foreach ($dateRange as $date) {
            $parts = $dailyParts->where('work_date', $date);
            if ($parts->count() > 0) {
                $daySeconds = 0;
                $dayFuel    = 0;
                foreach ($parts as $p) {
                    [$h, $m, $s] = array_pad(explode(':', $p->time_worked), 3, 0);
                    $daySeconds += ($h * 3600) + ($m * 60) + $s;
                    $dayFuel    += $p->initial_fuel ?? 0;
                }
                $hours = floor($daySeconds / 3600);
                $minutes = floor(($daySeconds % 3600) / 60);
                $timeWorkedFormatted = sprintf('%02d:%02d', $hours, $minutes);
                $equivalentHours = $hours + ($minutes / 60);
                $dailyAmount = $equivalentHours * $costPerHour;

                $processedData[] = [
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'time_worked' => $timeWorkedFormatted,
                    'equivalent_hours' => round($equivalentHours, 2),
                    'fuel_consumption' => $dayFuel,
                    'days_worked' => 1,
                    'cost_per_hour' => $costPerHour,
                    'total_amount' => round($dailyAmount, 2),
                    'has_work' => true
                ];

                $totalSecondsWorked += $daySeconds;
                $totalEquivalentHours += $equivalentHours;
                $totalFuelConsumption += $dayFuel;
                $totalDaysWorked++;
                $totalAmount += $dailyAmount;
            } else {
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

        $totalHours = floor($totalSecondsWorked / 3600);
        $totalMinutes = floor(($totalSecondsWorked % 3600) / 60);
        $totalTimeFormatted = sprintf('%02d:%02d', $totalHours, $totalMinutes);
        $totals = [
            'time_worked'      => $totalTimeFormatted,
            'equivalent_hours' => $totalEquivalentHours,
            'fuel_consumption' => $totalFuelConsumption,
            'days_worked'      => $totalDaysWorked,
            'cost_per_hour'    => $costPerHour,
            'total_amount'     => round($totalAmount, 2)
        ];

        $auth = [
            'minDate' => $minDateFormatted,
            'maxDate' => $maxDateFormatted,
            'processedData' => $processedData,
            'totals'  => $totals,
        ];

        /*$adjustment = DB::table('service_auth_adjustments')
            ->where('service_id', $serviceId)
            ->first();

        if ($adjustment) {
            // SI HAY AJUSTES: usar datos ajustados en lugar de los calculados
            $adjustedData = json_decode($adjustment->adjusted_data, true);
            $auth = $adjustedData;
            
            // Opcional: agregar flag para saber que son datos ajustados
            $auth['is_adjusted'] = true;
            $auth['last_adjustment'] = $adjustment->updated_at;
        } else {
            // SI NO HAY AJUSTES: usar datos calculados desde daily_parts
            $auth['is_adjusted'] = false;
        }*/

        //liquidation data
        $costPerDay = $totals['total_amount'] / $totals['days_worked'];
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        $totalInWords = strtoupper($formatter->format(floor($totals['total_amount'])));
        $cents = round(($totals['total_amount'] - floor($totals['total_amount'])) * 100);
        $totalInWordsComplete = $totalInWords . ' CON ' . sprintf('%02d', $cents) . '/100 SOLES';
        $liquidation = [
            'cost_per_day' => round($costPerDay, 2),
            'total_in_words' => $totalInWordsComplete
        ];

        return response()->json([
            'message' => 'Liquidation data retrieved successfully',
            'data' => [
                'equipment' => $equipment,
                'request' => $request,
                'auth' => $auth,
                'liquidation' => $liquidation
            ]
        ], 201);
    }

    public function generateRequest(Request $request)
    {
        Log::info('Generating request PDF with data: ', $request->all());
        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $qr_code = base64_encode("data_qr_example");
        $data = [
            'logoPath' => $logoPath,
            'equipment' => $request->equipment,
            'requestData' => $request->input('request'),
            'serviceId' => $request->serviceId,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.request_machinery', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Solicitud-de-movilidad.pdf');
    }

    public function generateAuth(Request $request)
    {
        $service = Service::find($request->serviceId);

        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $qr_code = base64_encode("data_qr_example");

        $data = [
            'logoPath' => $logoPath,
            'service' => $service,
            'equipment' => $request->equipment,
            'requestData' => $request->input('request'),
            'authData' => $request->auth,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.report_auth', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Autorización-de-servicio.pdf');
    }

    public function generateLiquidation(Request $request)
    {

        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $qr_code = base64_encode("data_qr_example");

        $data = [
            'logoPath' => $logoPath,
            'serviceId' => $request->serviceId,
            'equipment' => $request->equipment,
            'requestData' => $request->input('request'),
            'authData' => $request->auth,
            'liquidationData' => $request->liquidation,
            'qr_code' => $qr_code,
        ];

        $pdf = Pdf::loadView('pdf.liquidation_service', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('Liquidacion-servicio-alquiler.pdf');
    }

    public function saveAuthChanges(Request $request)
    {/*
        try {
            $serviceId = $request->input('serviceId');
            $authData = $request->input('authData');
            
            // Opción 1: Guardar en una tabla de ajustes/correcciones
            DB::table('service_auth_adjustments')->updateOrInsert(
                ['service_id' => $serviceId],
                [
                    'adjusted_data' => json_encode($authData),
                    'updated_at' => now(),
                    'updated_by' => auth()->id() // Si tienes autenticación
                ]
            );
            
            // Opción 2: Actualizar los registros originales en daily_parts
            // (solo si quieres modificar los datos originales)
            
            return response()->json([
                'message' => 'Cambios guardados exitosamente',
                'success' => true
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al guardar cambios',
                'error' => $e->getMessage()
            ], 500);
        }
    */}
}
