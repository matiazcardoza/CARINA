<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\MechanicalEquipment;
use App\Models\Operator;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function getLiquidationData($serviceId)
    {
        $service = Service::find($serviceId);
        $equipment = MechanicalEquipment::find($service->mechanical_equipment_id);
        $operators = Operator::where('service_id', $serviceId)->get();
        $equipment->operators = $operators;

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

        return response()->json([
            'message' => 'Liquidation data retrieved successfully',
            'data' => [
                'equipment' => $equipment,
                'request' => $request,
                'authorization' => $service->authorization,
                'liquidation' => $service->liquidation,
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
}
