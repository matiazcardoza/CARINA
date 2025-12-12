<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\WorkEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvidenceController extends Controller
{
    function getEvidence($serviceId, Request $request)
    {
        $stateValorized = $request->query('state_valorized', 1);
        $dailyParts = DailyPart::where('service_id', $serviceId)
            ->where('state_valorized', $stateValorized)
            ->select(
                'daily_parts.id',
                'daily_parts.description',
                'daily_parts.time_worked',
                'daily_parts.work_date',
                'daily_parts.shift_id',
                'daily_parts.state_valorized',
                'shifts.name as shift_name',
                'documents_daily_parts.state',
                'documents_daily_parts.id as document_id',
                'documents_daily_parts.file_path as path_document',
                'documents_daily_parts.user_id',
                'personas.name as user_name',
                'personas.last_name as user_lastname'
            )
            ->leftJoin('documents_daily_parts', 'daily_parts.document_id', '=', 'documents_daily_parts.id')
            ->leftJoin('shifts', 'daily_parts.shift_id', '=', 'shifts.id')
            ->leftJoin('personas', 'documents_daily_parts.user_id', '=', 'personas.user_id')
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

        return response()->json([
            'message' => 'Evidence fetched successfully',
            'data' => $dailyPartsWithEvidence
        ]);
    }
}
