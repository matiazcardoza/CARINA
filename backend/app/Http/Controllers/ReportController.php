<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\Service;
use App\Models\ServiceLiquidationAdjustment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

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
        list($hours, $minutes) = explode(':', $totalTimeFormatted);
        $totalEquivalentHoursTotal = $totalHours + ($totalMinutes / 60);
        $totalAmountTotal = $totalEquivalentHours * $costPerHour;
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

        $adjustment = ServiceLiquidationAdjustment::where('service_id', $serviceId)->first();

        if ($adjustment) {
            $adjustedData = json_decode($adjustment->adjusted_data, true);

            $equipment = (object) $adjustedData['equipment'];
            $request = $adjustedData['request'];
            $auth = $adjustedData['auth'];
            $liquidation = $adjustedData['liquidation'];

            $auth['is_adjusted'] = true;
            $auth['last_adjustment'] = $adjustment->updated_at;
        } else {
            $auth['is_adjusted'] = false;
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
        }

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
        Log::info('Valorization request data: ' . json_encode($request->json()->all()));
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
        $editedValorationAmount = $request->input('editedValorationAmount');
        $amountPlanilla = $request->input('amountPlanilla');
        $data = [
            'editedValorationAmount' => $editedValorationAmount,
            'amountPlanilla' => $amountPlanilla,
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
        try {
            $serviceId = $request->input('serviceId');
            $adjustedData = [
                'equipment' => $request->input('equipment'),
                'request' => $request->input('request'),
                'auth' => $request->input('auth'),
                'liquidation' => $request->input('liquidation'),
            ];
            ServiceLiquidationAdjustment::updateOrCreate(
                ['service_id' => $serviceId],
                [
                    'adjusted_data' => json_encode($adjustedData),
                    'updated_by' => Auth::id(),
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'message' => 'Cambios guardados exitosamente',
                'success' => true
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

    public function downloadMergedDailyParts($serviceId)
{
    // Obtener documentos
    $documents = DB::table('documents_daily_parts as ddp')
        ->join('daily_parts as dp', 'ddp.id', '=', 'dp.document_id')
        ->where('dp.service_id', $serviceId)
        ->whereIn('ddp.state', [1, 2, 3])
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

    // Rutas de entrada
    $inputFiles = [];
    foreach ($documents as $doc) {
        $inputFiles[] = storage_path('app/public/' . $doc->file_path);
    }

    // ⭐ DETECTAR HERRAMIENTAS DISPONIBLES (orden de preferencia)
    $pdftk = trim(shell_exec("which pdftk 2>/dev/null"));
    $qpdf = trim(shell_exec("which qpdf 2>/dev/null"));
    $gs = trim(shell_exec("which gs 2>/dev/null"));
    $pdfunite = trim(shell_exec("which pdfunite 2>/dev/null"));

    $success = false;

    // ═══════════════════════════════════════════════════════════
    // 🥇 MÉTODO 1: PDFTK (El mejor para preservar contenido)
    // ═══════════════════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════════════════
    // 🥈 MÉTODO 2: QPDF (Muy bueno, alternativa a pdftk)
    // ═══════════════════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════════════════
    // 🥉 MÉTODO 3: GHOSTSCRIPT (Con configuración optimizada)
    // ═══════════════════════════════════════════════════════════
    if ($gs && !$success) {
        $cmd = $gs . " -dBATCH -dNOPAUSE -dQUIET -dSAFER";
        $cmd .= " -sDEVICE=pdfwrite";
        $cmd .= " -dCompatibilityLevel=1.7";
        $cmd .= " -dPDFSETTINGS=/prepress"; // Máxima calidad
        $cmd .= " -dEmbedAllFonts=true";
        $cmd .= " -dSubsetFonts=true";
        $cmd .= " -dCompressFonts=false";
        $cmd .= " -dAutoRotatePages=/None";
        $cmd .= " -dColorImageResolution=300";
        $cmd .= " -dGrayImageResolution=300";
        $cmd .= " -dMonoImageResolution=1200";
        $cmd .= " -dDetectDuplicateImages=true";
        $cmd .= " -dDownsampleColorImages=false"; // No reducir calidad de imágenes
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

    // ═══════════════════════════════════════════════════════════
    // 🏅 MÉTODO 4: PDFUNITE (Rápido pero puede perder contenido)
    // ═══════════════════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════════════════
    // ❌ SIN HERRAMIENTAS DISPONIBLES
    // ═══════════════════════════════════════════════════════════
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
