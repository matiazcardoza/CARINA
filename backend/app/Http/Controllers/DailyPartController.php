<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use Illuminate\Http\Request;

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
}
