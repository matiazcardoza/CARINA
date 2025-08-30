<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    function index(Request $request)
    {
        $services = Service::select('orders_silucia.*', 'services.state', 'services.description')
                                    ->leftjoin('services', 'orders_silucia.id', '=', 'services.order_id')
                                    ->get();
        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $services
        ]);
    }
}
