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
        $dailyParts = DailyPart::where('service_id', $serviceId)
            ->select('id', 'description')
            ->get();
        $dailyPartsWithEvidence = $dailyParts->map(function ($dailyPart) {
            $evidences = WorkEvidence::where('daily_part_id', $dailyPart->id)
                ->select('id', 'daily_part_id', 'evidence_path', 'created_at')
                ->get();
            $dailyPart->evidences = $evidences->toArray();
            return $dailyPart;
        });
        
        Log::info('dailyParts with evidences: ', $dailyPartsWithEvidence->toArray());
        
        return response()->json([
            'message' => 'Evidence fetched successfully',
            'data' => $dailyPartsWithEvidence
        ]);
    }
}
