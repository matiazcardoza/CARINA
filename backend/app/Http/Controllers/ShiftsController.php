<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;

class ShiftsController extends Controller
{
    function consultaShifts(Request $request){
        $shifts = Shift::get();
        return response()->json([
            'message' => 'productos cargados correctamente',
            'data' => $shifts
        ]);
    }
}
