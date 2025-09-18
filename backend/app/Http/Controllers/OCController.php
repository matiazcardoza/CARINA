<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdenCompra;
class OCController extends Controller
{
    // GET /api/ordenes-compra
    public function index(Request $request)
    {
        // return "retorna ordenes de compra";
        // Gracias al global-scope por obra (si lo agregaste) y/o al team activo,
        // la consulta ya queda acotada a la obra actual.
        $ocs = OrdenCompra::query()
            ->select('id','obra_id','ext_order_id','fecha','proveedor','monto_total','created_at')
            ->orderByDesc('fecha')
            // ->paginate(20);
            ->get();

        return response()->json($ocs);
    }

    public function pecosas(Request $request, OrdenCompra $orden)
    {
        // El route-model binding + BelongsToObra garantiza que {orden} sea de la obra activa
        if (! $request->user()->hasRole(['admin_obra','almacenero_principal','almacenero_auxiliar','visor'])) {
            abort(403, 'No autorizado para ver pecosas en esta obra');
        }

        // Puedes paginar si lo prefieres; aquí devolvemos todas para la tabla hija
        $items = $orden->items()
            ->orderByDesc('fecha')
            ->orderBy('id')
            ->get([
                'id','obra_id','orden_id',
                'anio','fecha','numero','idsalidadet',
                'cantidad_compra as cantidad',
                'precio_unit as precio',
                'unidad as desmedida',
                'descripcion as item',
                'prod_proy','cod_meta','desmeta','desuoper','destipodestino',
            ]);

        return response()->json($items);
    }
}
