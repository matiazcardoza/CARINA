<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\OrderSilucia;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyPartController extends Controller
{
    function index(Request $request)
    {
        $serviceId = $request->id;
        $dailyParts = DailyPart::where('service_id', $serviceId)->get();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $dailyParts
        ]);
    }

    function store(Request $request)
    {
        $dailyPart = DailyPart::create([
            'service_id' => $request->work_log_id,
            'work_date' => $request->work_date,
            'start_time' => $request->start_time,
            'initial_fuel' => $request->initial_fuel,
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Daily work log created successfully',
            'data' => $dailyPart
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $dailyPart = DailyPart::findOrFail($id);

            $validatedData = $request->validate([
                'work_date' => 'required|date',
                'start_time' => 'required|date_format:H:i:s',
                'initial_fuel' => 'nullable|numeric|min:0',
            ]);

            if (isset($validatedData['start_time'])) {
                $time = $validatedData['start_time'];
                if (substr_count($time, ':') === 1) {
                    $validatedData['start_time'] = $time . ':00';
                }
            }

            $dailyPart->refresh();

            return response()->json([
                'message' => 'Daily work log updated successfully',
                'data' => $dailyPart
            ], 200);
    }

    public function destroy($id)
    {
        $dailyPart = DailyPart::findOrFail($id);
        $dailyPart->delete();

        return response()->json([
            'message' => 'Daily work log deleted successfully'
        ], 204);
    }

    public function completeWork(Request $request)
    {
        $worlkLogId = $request->workLogId;        
            $dailyPart = DailyPart::find($worlkLogId);
            $dailyPart->end_time = $request->end_time;
            $dailyPart->final_fuel = $request->final_fuel;
            $start = Carbon::parse($dailyPart->start_time);
            $end = Carbon::parse($dailyPart->end_time);
            $interval = $start->diff($end);
            $hours = $interval->h;
            $minutes = $interval->i;
            $timeWorked = $hours + ($minutes / 60);
            $dailyPart->time_worked = $timeWorked;
            $dailyPart->fuel_consumed = $dailyPart->final_fuel - $dailyPart->initial_fuel;
        $dailyPart->save();

        $service = Service::find($dailyPart->service_id);
        $service->occurrences = $request->occurrence;
        $service->save();



        return response()->json([
            'message' => 'Daily work log completed successfully',
            'data' => $dailyPart
        ], 200);
    }

    public function generatePdf($serviceId)
    {
        $service = Service::find($serviceId);
        $orderSilucia = OrderSilucia::find($service->order_id);
        $dailyPart = DailyPart::where('service_id', $serviceId)->get();
        Log::info("infomacion de pdf". $dailyPart);
        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $logoWorkPath = storage_path('app/public/image_pdf_template/logo_work.png');
        $qr_code = base64_encode("data_qr_example");
        $data = [
            'logoPath' => $logoPath,
            'orderSilucia' => $orderSilucia,
            'logoWorkPath' => $logoWorkPath,
            'dailyPart' => $dailyPart,
            'pdf' => true,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.daily_part', $data);
        
        // Configurar opciones del PDF si es necesario
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->stream('anexo_01_planilla.pdf');
    }
}
