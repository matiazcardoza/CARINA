<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyPartController extends Controller
{
    function index(Request $request)
    {
        $dailyParts = DailyPart::all();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $dailyParts
        ]);
    }

    function store(Request $request)
    {
        $validatedData = $request->validate([
            'work_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'initial_fuel' => 'required|numeric',
        ]);

        $validatedData['service_id'] = 1;

        $dailyPart = DailyPart::create($validatedData);

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

        return response()->json([
            'message' => 'Daily work log completed successfully',
            'data' => $dailyPart
        ], 200);
    }
}
