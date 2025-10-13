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
            ->select('daily_parts.id', 'daily_parts.description', 'daily_parts.time_worked', 'daily_parts.work_date', 'documents_daily_parts.state')
            ->leftJoin('documents_daily_parts', 'daily_parts.document_id', '=', 'documents_daily_parts.id')
            ->get()
            ->groupBy('work_date');

        $dailyPartsWithEvidence = $dailyParts->map(function ($group) {
            return $group->map(function ($dailyPart) {
                $evidences = WorkEvidence::where('daily_part_id', $dailyPart->id)
                    ->select('id', 'daily_part_id', 'evidence_path', 'created_at')
                    ->get();
                $dailyPart->evidences = $evidences->toArray();
                return $dailyPart;
            });
        });

        return response()->json([
            'message' => 'Evidence fetched successfully',
            'data' => $dailyPartsWithEvidence
        ]);
    }
}
