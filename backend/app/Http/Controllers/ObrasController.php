<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObrasController extends Controller
{
  public function mine(Request $request) {
    $rows = DB::table('obra_user')
      ->join('obras','obras.id','=','obra_user.obra_id')
      ->where('obra_user.user_id', $request->user()->id)
      ->select('obras.id','obras.nombre','obras.codigo')
      ->orderBy('obras.nombre')
      ->get();

    return response()->json($rows);
  }
}
