<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObrasController extends Controller
{
  public function mine(Request $request) {
    // return "obras001";
    $rows = DB::table('obra_user')
      ->join('obras','obras.id','=','obra_user.obra_id')
      ->where('obra_user.user_id', $request->user()->id)
      // ->select('obras.id','obras.nombre','obras.codigo')
      ->select(
        'obras.id',
        'obras.idmeta_silucia',
        'obras.anio',
        'obras.codmeta',
        'obras.nombre',
        'obras.desmeta',
        'obras.nombre_corto',
        'obras.cadena',
        'obras.prod_proy',
        'obras.external_last_seen_at',
        'obras.external_hash',
        'obras.raw_snapshot',
      )
      ->orderBy('obras.nombre')
      ->get();

    return response()->json($rows);
  }
}
