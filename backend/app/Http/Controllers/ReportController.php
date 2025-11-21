<?php

namespace App\Http\Controllers;

use App\Models\MechanicalEquipment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function getLiquidationData($serviceId)
    {
        $service = Service::find($serviceId);
        $equipment = MechanicalEquipment::find($service->mechanical_equipment_id);

        return response()->json([
            'message' => 'Liquidation data retrieved successfully',
            'data' => [
                'equipment' => $equipment,
                'request' => $service->request,
                'authorization' => $service->authorization,
                'liquidation' => $service->liquidation,
            ]
        ], 201);
    }
}
