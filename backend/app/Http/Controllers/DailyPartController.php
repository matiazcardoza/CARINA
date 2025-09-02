<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\MechanicalEquipment;
use App\Models\OrderSilucia;
use App\Models\Service;
use App\Models\WorkEvidence;
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
            'service_id' => $request->service_id,
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
        $dailyPart = DailyPart::find($request->workLogId);

        $start = Carbon::createFromFormat('H:i:s', $dailyPart->start_time);
        $end = Carbon::createFromFormat('H:i', $request->end_time);

        if ($end->lessThan($start)) {
            $end->addDay(); 
        }

        $diffInSeconds = $end->diffInSeconds($start);
        $workedTime = gmdate('H:i:s', $diffInSeconds);

        $dailyPart->update([
            'end_time'    => $request->end_time,
            'occurrences' => $request->occurrence,
            'time_worked' => $workedTime,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $timestamp = now()->format('YmdHis');
                $extension = $image->getClientOriginalExtension();
                $fileName = "{$dailyPart->id}_evidence_{$index}_{$timestamp}.{$extension}";
                
                $path = $image->storeAs('work_evidences', $fileName, 'public');
                
                WorkEvidence::create([
                    'daily_part_id' => $dailyPart->id,
                    'evidence_path' => $path
                ]);
            }
        }

        return response()->json([
            'message' => 'Daily work log completed successfully',
        ], 200);
    }

    public function generatePdf($serviceId)
    {

        $service = Service::find($serviceId);
        $orderSilucia = OrderSilucia::find($service->order_id);
        $dailyPart = DailyPart::where('service_id', $serviceId)->get();
        $mechanicalEquipment = MechanicalEquipment::find($service->mechanical_equipment_id);
        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $logoWorkPath = storage_path('app/public/image_pdf_template/logo_work.png');
        $qr_code = base64_encode("data_qr_example");
        $data = [
            'logoPath' => $logoPath,
            'logoWorkPath' => $logoWorkPath,
            'orderSilucia' => $orderSilucia,
            'mechanicalEquipment' => $mechanicalEquipment,
            'service' => $service,
            'dailyPart' => $dailyPart,
            'pdf' => true,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.daily_part', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('anexo_01_planilla.pdf');
    }
}
