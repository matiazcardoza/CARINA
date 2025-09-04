<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    function index(Request $request)
    {
        $services = Service::select('services.*',
                                        'orders_silucia.supplier',
                                        'orders_silucia.machinery_equipment',
                                        'mechanical_equipment.machinery_equipment as mechanicalEquipment')
                                    ->leftJoin('orders_silucia', 'services.order_id', '=', 'orders_silucia.id')
                                    ->leftJoin('mechanical_equipment', 'services.mechanical_equipment_id', '=', 'mechanical_equipment.id')
                                    ->get();
        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $services
        ]);
    }

    function selectedData(Request $request)
    {
        $services = Service::select('goal_id', 'goal_project', 'goal_detail')
            ->distinct()
            ->get();

        return response()->json([
            'message' => 'Unique goals retrieved successfully',
            'data' => $services
        ]);
    }


    function getDailyPartsData($idGoal)
    {
        $services = Service::where('goal_id', $idGoal)->get();

        $servicesWithTotalTime = $services->map(function ($service) {
            $dailyParts = DailyPart::where('service_id', $service->id)->get();

            $totalSeconds = $dailyParts->reduce(function ($carry, $item) {
                // Validar que el valor no es nulo y tiene el formato correcto
                if ($item->time_worked && str_contains($item->time_worked, ':')) {
                    [$hours, $minutes, $seconds] = explode(':', $item->time_worked);
                    
                    // Asegurar que las partes son numéricas y no nulas
                    $hours = is_numeric($hours) ? (int)$hours : 0;
                    $minutes = is_numeric($minutes) ? (int)$minutes : 0;
                    $seconds = is_numeric($seconds) ? (int)$seconds : 0;

                    return $carry + ($hours * 3600) + ($minutes * 60) + $seconds;
                }

                return $carry;
            }, 0);

            $totalTimeWorked = gmdate('H:i:s', $totalSeconds);

            $service->total_time_worked = $totalTimeWorked;

            return $service;
        });

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $servicesWithTotalTime
        ]);
    }
}
