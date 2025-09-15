<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenCompra;
class OCController extends Controller
{
    // GET /api/ordenes-compra
    public function index(Request $request)
    {
        return "hola mundo";
        // Gracias al global-scope por obra (si lo agregaste) y/o al team activo,
        // la consulta ya queda acotada a la obra actual.
        // $ocs = OrdenCompra::query()
        //     ->select('id','obra_id','ext_order_id','fecha','proveedor','monto_total','created_at')
        //     ->orderByDesc('fecha')
        //     ->paginate(20);

        return response()->json($ocs);
    }
}
