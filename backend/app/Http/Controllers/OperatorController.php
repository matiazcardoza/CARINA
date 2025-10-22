<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function getOperators($serviceId)
    {
        $operators = Operator::where('service_id', $serviceId)->get();

        return response()->json([
            'message' => 'Operadores cargados correctamente',
            'data' => $operators
        ], 201);
    }
}
