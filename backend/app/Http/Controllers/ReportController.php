<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\Service;
use App\Models\ServiceLiquidationAdjustment;
use App\Models\ValorationAdjustment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $minDate = DailyPart::where('service_id', $serviceId)->where('state_valorized', 1)->min('work_date');
        $maxDate = DailyPart::where('service_id', $serviceId)->where('state_valorized', 1)->max('Work_date');
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
        list($hours, $minutes) = explode(':', $totalTimeFormatted);
        $totalEquivalentHoursTotal = $totalHours + ($totalMinutes / 60);
        $totalAmountTotal = round($totalEquivalentHours, 2) * round($costPerHour, 2);
        $totals = [
            'time_worked'      => $totalTimeFormatted,
            'equivalent_hours' => round($totalEquivalentHoursTotal, 2),
            'fuel_consumption' => $totalFuelConsumption,
            'days_worked'      => $totalDaysWorked,
            'cost_per_hour'    => $costPerHour,
            'total_amount'     => round($totalAmountTotal, 2)
        ];

        $auth = [
            'minDate' => $minDateFormatted,
            'maxDate' => $maxDateFormatted,
            'processedData' => $processedData,
            'totals'  => $totals,
        ];

        $costPerDay = $totals['days_worked'] > 0
            ? $totals['total_amount'] / $totals['days_worked']
            : 0;

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

    public function getValorationData($goalId){
        $services = Service::join('daily_parts', 'services.id', '=', 'daily_parts.service_id')
                ->where('services.goal_id', $goalId)
                ->where('services.state_valorized', '!=', 2)
                ->select('services.*')
                ->distinct()
                ->get();

        $machinery = [];
        $totalValorationAmount = 0;

        foreach ($services as $service) {
            if ($service->state != 3) {
                continue;
            }
            $adjustment = ServiceLiquidationAdjustment::where('service_id', $service->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

            if (!$adjustment) {
                $dailyParts = DailyPart::where('service_id', $service->id)->get();
                $totalSecondsWorked = 0;
                foreach ($dailyParts as $part) {
                    [$h, $m, $s] = array_pad(explode(':', $part->time_worked), 3, 0);
                    $totalSecondsWorked += ($h * 3600) + ($m * 60) + $s;
                }

                $totalHours = floor($totalSecondsWorked / 3600);
                $totalMinutes = floor(($totalSecondsWorked % 3600) / 60);
                $totalTimeFormatted = sprintf('%02d:%02d', $totalHours, $totalMinutes);
                $service->time_worked = $totalTimeFormatted;
                continue;
            }
            $adjustedData = json_decode($adjustment->adjusted_data, true);
            $equipment = (object) $adjustedData['equipment'];
            $totalTimeFormatted = $adjustedData['auth']['totals']['time_worked'] ?? '00:00';
            $totalEquivalentHours = $adjustedData['auth']['totals']['equivalent_hours'] ?? 0;
            $totalDaysWorked = $adjustedData['auth']['totals']['days_worked'] ?? 0;
            $costPerHour = $adjustedData['auth']['totals']['cost_per_hour'] ?? 0;
            $totalAmount = $adjustedData['auth']['totals']['total_amount'] ?? 0;
            $costPerDay = $adjustedData['liquidation']['cost_per_day'] ?? 0;

            $dailyParts = DailyPart::where('service_id', $service->id)->get();
                $totalSecondsWorked = 0;
                foreach ($dailyParts as $part) {
                    [$h, $m, $s] = array_pad(explode(':', $part->time_worked), 3, 0);
                    $totalSecondsWorked += ($h * 3600) + ($m * 60) + $s;
                }

                $totalHours = floor($totalSecondsWorked / 3600);
                $totalMinutes = floor(($totalSecondsWorked % 3600) / 60);
                $totalTimeFormatted = sprintf('%02d:%02d', $totalHours, $totalMinutes);
                $service->time_worked = $totalTimeFormatted;

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
            $totalValorationAmount += $totalAmount;
        }

        $goalService = Service::where('goal_id', $goalId)->first();

        $valoration = [
            'goal' => $goalService,
            'machinery' => $machinery,
            'valoration_amount' => round($totalValorationAmount, 2)
        ];

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $valoration,
        ]);
    }

    public function getAdjustedLiquidationData($serviceId)
    {
        $adjustments = ServiceLiquidationAdjustment::where('service_id', $serviceId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($adjustment) {
                $adjustedData = json_decode($adjustment->adjusted_data, true);
                if (!isset($adjustedData['request']['record'])) {
                    $adjustedData['request']['record'] = [
                        'num_reg' => $adjustment->num_reg,
                        'created_at' => $adjustment->created_at
                    ];
                }
                return [
                    'id' => $adjustment->id,
                    'num_reg' => $adjustment->num_reg,
                    'created_at' => $adjustment->created_at,
                    'updated_at' => $adjustment->updated_at,
                    'updated_by' => $adjustment->updated_by,
                    'adjusted_data' => $adjustedData
                ];
            });

        return response()->json([
            'message' => 'Historial de ajustes obtenido correctamente',
            'data' => $adjustments
        ], 200);
    }

    public function getAdjustedValorationData($goalId){
        $adjustments = ValorationAdjustment::where('goal_id', $goalId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($adjustment) {
                $adjustedData = json_decode($adjustment->adjusted_data, true);

                $deductiveOrder = json_decode($adjustment->deductive_order, true);
                $deductiveSheet = json_decode($adjustment->deductive_sheet, true);

                $adjustedData['deductives'] = [
                    'deductive_order' => $deductiveOrder ?? [],
                    'deductive_sheet' => $deductiveSheet ?? [],
                ];

                if (!isset($adjustedData['record'])) {
                    $adjustedData['record'] = [
                        'num_reg' => $adjustment->num_reg,
                        'created_at' => $adjustment->created_at
                    ];
                }
                return [
                    'id' => $adjustment->id,
                    'num_reg' => $adjustment->num_reg,
                    'created_at' => $adjustment->created_at,
                    'updated_at' => $adjustment->updated_at,
                    'updated_by' => $adjustment->updated_by,
                    'adjusted_data' => $adjustedData
                ];
            });

        return response()->json([
            'message' => 'Historial de ajustes obtenido correctamente',
            'data' => $adjustments
        ], 200);
    }

    public function generateRequest(Request $request)
    {
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

    public function generateValorization(Request $request)
    {
        $serviceId = $request->machinery[0]['service_id'];

        $goalDetail = DB::table('services')
            ->where('id', $serviceId)
            ->value('goal_detail');
        $maxWorkedDate = DB::table('daily_parts')
            ->where('service_id', $serviceId)
            ->max('work_date');
        $mes = strtoupper(
            \Carbon\Carbon::parse($maxWorkedDate)
                ->locale('es')
                ->monthName
        );
        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $qr_code = base64_encode("data_qr_example");
        $valorationData = $request->json()->all();
        $amountValoration = $request->input('valoration_amount');
        $amountFinal = $request->input('amountFinal');
        $amountOrders = $request->input('amountOrders');
        $monthlySummary = $request->input('monthlySummary');
        $data = [
            'record' => $request->record,
            'amountValoration' => $amountValoration,
            'amountFinal' => $amountFinal,
            'amountOrders' => $amountOrders,
            'monthlySummary'=> $monthlySummary,
            'goalDetail' => $goalDetail,
            'mes' => $mes,
            'logoPath' => $logoPath,
            'valorationData' => $valorationData,
            'qr_code' => $qr_code,
        ];

        $pdf = Pdf::loadView('pdf.valorization_goal', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('Valoracion-goal.pdf');
    }

    public function saveAuthChanges(Request $request)
    {
        if($request->input('auth.totals.total_amount') <= 0){
            return response()->json([
                'message' => 'no se puede liquidar si el monto total no es un valor valido.',
                'success' => false
            ], 400);
        }
        $requestMaxDate = $request->input('request.maxDate');
        $requestMinDate = $request->input('request.minDate');
        $serviceId = $request->input('serviceId');

        $latestAdjustment = ServiceLiquidationAdjustment::where('service_id', $serviceId)
                ->orderBy('created_at', 'desc')
                ->first();
        $shouldUpdate = false;
        if ($latestAdjustment) {
            $shouldUpdate = $latestAdjustment->created_at->isSameDay(now());
            $dataLatestAdjustment = json_decode($latestAdjustment->adjusted_data, true);
            $latestMaxDate = $dataLatestAdjustment['request']['maxDate'];
            $latestMinDate = $dataLatestAdjustment['request']['minDate'];
            $dateLatest = Carbon::parse($latestMaxDate);
            $dateMinLatest = Carbon::parse($latestMinDate);
            $dateMinRequest = Carbon::parse($requestMinDate);
            $dateRequest = Carbon::parse($requestMaxDate);
        }

        try {
            $adjustmentId = $request->input('adjustmentId');
            $adjustedData = [
                'equipment' => $request->input('equipment'),
                'request' => $request->input('request'),
                'auth' => $request->input('auth'),
                'liquidation' => $request->input('liquidation'),
            ];

            $currentYear = date('Y');

            if ($adjustmentId !== null) {
                $adjustment = ServiceLiquidationAdjustment::find($adjustmentId);
                $adjustment->update([
                    'adjusted_data' => json_encode($adjustedData),
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);

                if ($dateLatest->gt($dateRequest) && $latestAdjustment->id == $adjustmentId) {
                    $startDate = $dateRequest->copy()->addDay()->toDateString();
                    $endDate = $dateLatest->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startDate, $endDate])
                        ->update(['state_valorized' => 1]);
                } elseif ($dateLatest->lt($dateRequest) && $latestAdjustment->id == $adjustmentId) {
                    $startDate = $dateLatest->copy()->addDay()->toDateString();
                    $endDate = $dateRequest->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startDate, $endDate])
                        ->update(['state_valorized' => 2]);
                }

                if ($dateMinLatest->lt($dateMinRequest)) {
                    $startMinDate = $dateMinLatest->toDateString();
                    $endMinDate = $dateMinRequest->copy()->subDay()->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startMinDate, $endMinDate])
                        ->update(['state_valorized' => 1]);
                } elseif ($dateMinLatest->gt($dateMinRequest)) {
                    $startMinDate = $dateMinRequest->toDateString();
                    $endMinDate = $dateMinLatest->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startMinDate, $endMinDate])
                        ->update(['state_valorized' => 2]);
                }

                return response()->json([
                    'message' => 'Registro actualizado exitosamente',
                    'success' => true,
                    'data' => [
                        'adjustment_id' => $adjustment->id,
                        'record' => [
                            'num_reg' => $adjustment->num_reg,
                            'created_at' => $adjustment->created_at,
                            'updated_at' => $adjustment->updated_at
                        ]
                    ]
                ], 200);
            }

            $lastRecord = ServiceLiquidationAdjustment::whereYear('created_at', $currentYear)
                    ->orderBy('num_reg', 'desc')
                    ->first();

            if ($shouldUpdate) {
                $latestAdjustment->update([
                    'adjusted_data' => json_encode($adjustedData),
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);

                if ($dateLatest->gt($dateRequest)) {
                    $startDate = $dateRequest->copy()->addDay()->toDateString();
                    $endDate = $dateLatest->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startDate, $endDate])
                        ->update(['state_valorized' => 1]);
                } elseif ($dateLatest->lt($dateRequest)) {
                    $startDate = $dateLatest->copy()->addDay()->toDateString();
                    $endDate = $dateRequest->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startDate, $endDate])
                        ->update(['state_valorized' => 2]);
                }

                if ($dateMinLatest->lt($dateMinRequest)) {
                    $startMinDate = $dateMinLatest->toDateString();
                    $endMinDate = $dateMinRequest->copy()->subDay()->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startMinDate, $endMinDate])
                        ->update(['state_valorized' => 1]);
                } elseif ($dateMinLatest->gt($dateMinRequest)) {
                    $startMinDate = $dateMinRequest->toDateString();
                    $endMinDate = $dateMinLatest->toDateString();
                    DailyPart::where('service_id', $serviceId)
                        ->whereBetween('work_date', [$startMinDate, $endMinDate])
                        ->update(['state_valorized' => 2]);
                }

                $adjustment = $latestAdjustment;
            } else {
                $newNumReg = $lastRecord ? $lastRecord->num_reg + 1 : 1;
                $adjustment = ServiceLiquidationAdjustment::create([
                    'service_id' => $serviceId,
                    'adjusted_data' => json_encode($adjustedData),
                    'num_reg' => $newNumReg,
                    'updated_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DailyPart::where('service_id', $serviceId)
                    ->where('state_valorized', 1)
                    ->where('work_date', '<=', $requestMaxDate)
                    ->update(['state_valorized' => 2]);
            }

            return response()->json([
                'message' => 'Cambios guardados exitosamente',
                'success' => true,
                'data' => [
                    'record' => [
                        'num_reg' => $adjustment->num_reg,
                        'created_at' => $adjustment->created_at
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error saving auth changes: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al guardar cambios',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function saveValoration(Request $request){
        if ($request->input('valorationData.valoration_amount') <= 0){
            return response()->json([
                'message' => 'no se puede valorar si el monto total no es un valor valido.',
                'success' => false
            ], 400);
        }

        $adjustmentId = $request->input('adjustmentId');
        $goalId = $request->input('goalId');
        $deductives = $request->input('deductives');

        $deductiveOrder = null;
        $deductiveSheet = null;

        if (!empty($deductives['deductive_order'])){
            $deductiveOrder = json_encode([
                'deductive_order' => $deductives['deductive_order'],
                'amount_orders' => $deductives['amountOrders']
            ]);
        }

        if (!empty($deductives['deductive_sheet'])){
            $deductiveSheet = json_encode([
                'deductive_sheet' => $deductives['deductive_sheet'],
                'monthlySummary'  => $deductives['monthlySummary']
            ]);
        }

        try{
            $lastValorization = ValorationAdjustment::where('goal_id', $goalId)
                                ->orderBy('created_at', 'desc')
                                ->first();

            $shouldUpdate = false;
            if ($lastValorization) {
                $shouldUpdate = $lastValorization->created_at->isSameDay(now());
            }

            $currentYear = date('Y');
            $lastRecord = ValorationAdjustment::whereYear('created_at', $currentYear)
                        ->orderBy('num_reg', 'desc')
                        ->first();
            $newNumReg = $lastRecord ? $lastRecord->num_reg + 1 : 1;
            $valorationData = $request->valorationData;

            if ($adjustmentId !== null){
                $adjustment = ValorationAdjustment::find($adjustmentId);
                $adjustment->update([
                    'adjusted_data' => json_encode($valorationData),
                    'deductive_order' => $deductiveOrder,
                    'deductive_sheet' => $deductiveSheet,
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);
            } elseif ($shouldUpdate) {
                $adjustment = $lastValorization;
                $adjustment->update([
                    'adjusted_data' => json_encode($valorationData),
                    'deductive_order' => $deductiveOrder,
                    'deductive_sheet' => $deductiveSheet,
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]);
            } else {
                $adjustment = ValorationAdjustment::create([
                    'goal_id' => $goalId,
                    'adjusted_data' => json_encode($valorationData),
                    'deductive_order' => $deductiveOrder,
                    'deductive_sheet' => $deductiveSheet,
                    'num_reg' => $newNumReg,
                    'updated_by' => Auth::id()
                ]);
            }

            return response()->json([
                'message' => 'Cambios guardados exitosamente',
                'success' => true,
                'data' => [
                    'record' => [
                        'num_reg' => $adjustment->num_reg,
                        'created_at' => $adjustment->created_at
                    ]
                ]
            ], 200);

        } catch (\Exception $e){
            Log::error('Error saving valoration changes: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al guardar cambios de valoración',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
    public function downloadMergedDailyParts($serviceId, $stateValorized)
    {
        $documents = DB::table('documents_daily_parts as ddp')
            ->join('daily_parts as dp', 'ddp.id', '=', 'dp.document_id')
            ->where('dp.service_id', $serviceId)
            ->whereIn('ddp.state', [3])
            ->whereIn('dp.state_valorized', [$stateValorized])
            ->select('ddp.id', 'ddp.file_path', 'ddp.created_at', 'dp.work_date')
            ->groupBy('ddp.id', 'ddp.file_path', 'ddp.created_at', 'dp.work_date')
            ->orderBy('dp.work_date', 'asc')
            ->get();

            if ($documents->isEmpty()) {
                return response()->json(['ok' => false, 'error' => 'No hay documentos'], 404);
            }
            $tempDir = storage_path('app/public/temp_downloads');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

        $outputFile = $tempDir . '/unido_' . time() . '.pdf';
        $inputFiles = [];
        foreach ($documents as $doc) {
            $inputFiles[] = storage_path('app/public/' . $doc->file_path);
        }
        $pdftk = trim(shell_exec("which pdftk 2>/dev/null"));
        $qpdf = trim(shell_exec("which qpdf 2>/dev/null"));
        $gs = trim(shell_exec("which gs 2>/dev/null"));
        $pdfunite = trim(shell_exec("which pdfunite 2>/dev/null"));

        $success = false;
        if ($pdftk && !$success) {
            $cmd = $pdftk;
            foreach ($inputFiles as $file) {
                $cmd .= " " . escapeshellarg($file);
            }
            $cmd .= " cat output " . escapeshellarg($outputFile);

            exec($cmd . " 2>&1", $out, $ret);

            if ($ret === 0 && file_exists($outputFile)) {
                $success = true;
            } else {
                Log::warning('pdftk falló: ' . implode("\n", $out));
            }
        }
        if ($qpdf && !$success) {
            $cmd = $qpdf . " --empty --pages";
            foreach ($inputFiles as $file) {
                $cmd .= " " . escapeshellarg($file);
            }
            $cmd .= " -- " . escapeshellarg($outputFile);

            exec($cmd . " 2>&1", $out, $ret);

            if ($ret === 0 && file_exists($outputFile)) {
                $success = true;
            } else {
                Log::warning('qpdf falló: ' . implode("\n", $out));
            }
        }
        if ($gs && !$success) {
            $cmd = $gs . " -dBATCH -dNOPAUSE -dQUIET -dSAFER";
            $cmd .= " -sDEVICE=pdfwrite";
            $cmd .= " -dCompatibilityLevel=1.7";
            $cmd .= " -dPDFSETTINGS=/prepress";
            $cmd .= " -dEmbedAllFonts=true";
            $cmd .= " -dSubsetFonts=true";
            $cmd .= " -dCompressFonts=false";
            $cmd .= " -dAutoRotatePages=/None";
            $cmd .= " -dColorImageResolution=300";
            $cmd .= " -dGrayImageResolution=300";
            $cmd .= " -dMonoImageResolution=1200";
            $cmd .= " -dDetectDuplicateImages=true";
            $cmd .= " -dDownsampleColorImages=false";
            $cmd .= " -dDownsampleGrayImages=false";
            $cmd .= " -dDownsampleMonoImages=false";
            $cmd .= " -sOutputFile=" . escapeshellarg($outputFile);

            foreach ($inputFiles as $file) {
                $cmd .= " " . escapeshellarg($file);
            }

            exec($cmd . " 2>&1", $out, $ret);

            if ($ret === 0 && file_exists($outputFile)) {
                $success = true;
            } else {
                Log::warning('ghostscript falló: ' . implode("\n", $out));
            }
        }
        if ($pdfunite && !$success) {
            $cmd = $pdfunite . ' ';
            foreach ($inputFiles as $file) {
                $cmd .= escapeshellarg($file) . ' ';
            }
            $cmd .= escapeshellarg($outputFile);

            exec($cmd . " 2>&1", $out, $ret);

            if ($ret === 0 && file_exists($outputFile)) {
                $success = true;
            } else {
                Log::warning('pdfunite falló: ' . implode("\n", $out));
            }
        }
        if (!$success) {
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo unir los PDFs. Herramientas probadas: pdftk, qpdf, ghostscript, pdfunite',
                'available_tools' => [
                    'pdftk' => !empty($pdftk),
                    'qpdf' => !empty($qpdf),
                    'ghostscript' => !empty($gs),
                    'pdfunite' => !empty($pdfunite)
                ]
            ], 500);
        }

        return response()->download($outputFile)->deleteFileAfterSend(true);
    }

    public function  closeService($serviceId){
        $service = Service::find($serviceId);
        $service->update([
            'state_valorized' => 2
        ]);

        return response()->json([
            'message' => 'Servicio cerrado correctamente'
        ], 200);
    }
}
