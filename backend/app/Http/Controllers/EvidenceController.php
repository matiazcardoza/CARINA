<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\WorkEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvidenceController extends Controller
{
    function getEvidence($serviceId)
    {
        // Traer todos los daily_parts del servicio
        $dailyParts = DailyPart::where('service_id', $serviceId)->pluck('id');

        // Traer evidencias relacionadas y agruparlas por daily_part_id
        $evidence = WorkEvidence::whereIn('daily_part_id', $dailyParts)
                    ->get()
                    ->groupBy('daily_part_id');

        Log::info('evidence: ', $evidence->toArray());

        return $evidence;
    }
}
