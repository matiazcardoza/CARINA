<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

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
}
