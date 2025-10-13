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
            ->select('daily_parts.*', 'documents_daily_parts.state as document_state')
            ->leftJoin('documents_daily_parts', 'daily_parts.document_id', '=', 'documents_daily_parts.id')
            ->get();
        $dailyPartsWithEvidence = $dailyParts->map(function ($dailyPart) {
            $evidences = WorkEvidence::where('daily_part_id', $dailyPart->id)
                ->select('id', 'daily_part_id', 'evidence_path', 'created_at')
                ->get();
            $dailyPart->evidences = $evidences->toArray();
            return $dailyPart;
        });

        return response()->json([
            'message' => 'Evidence fetched successfully',
            'data' => $dailyPartsWithEvidence
        ]);
    }
}
