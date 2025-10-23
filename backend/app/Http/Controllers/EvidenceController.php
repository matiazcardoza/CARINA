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
            ->select(
                'daily_parts.id', 
                'daily_parts.description', 
                'daily_parts.time_worked', 
                'daily_parts.work_date',
                'daily_parts.shift_id',
                'shifts.name as shift_name',
                'documents_daily_parts.state',
                'documents_daily_parts.id as document_id',
                'documents_daily_parts.file_path as path_document'
            )
            ->leftJoin('documents_daily_parts', 'daily_parts.document_id', '=', 'documents_daily_parts.id')
            ->leftJoin('shifts', 'daily_parts.shift_id', '=', 'shifts.id')
            ->get()
            ->groupBy('work_date')
            ->map(function ($dateGroup) {
                return $dateGroup->groupBy('shift_id');
            });

        $dailyPartsWithEvidence = $dailyParts->map(function ($dateGroup) {
            return $dateGroup->map(function ($shiftGroup) {
                $items = $shiftGroup->map(function ($dailyPart) {
                    $evidences = WorkEvidence::where('daily_part_id', $dailyPart->id)
                        ->select('id', 'daily_part_id', 'evidence_path', 'created_at')
                        ->get();
                    
                    $dailyPart->evidences = $evidences->toArray();
                    return $dailyPart;
                });

                return [
                    'shift_id' => $shiftGroup->first()->shift_id,
                    'shift_name' => $shiftGroup->first()->shift_name ?? 'DÍA COMPLETO',
                    'document_id' => $shiftGroup->first()->document_id,
                    'path_document' => $shiftGroup->first()->path_document,
                    'state' => $shiftGroup->first()->state ?? 0,
                    'items' => $items->values()
                ];
            })->values();
        });

        Log::info('este es el dailyPartsWithEvidence: ' . $dailyPartsWithEvidence);

        return response()->json([
            'message' => 'Evidence fetched successfully',
            'data' => $dailyPartsWithEvidence
        ]);
    }
}
