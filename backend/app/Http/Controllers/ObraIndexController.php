<?php

// namespace App\Http\Controllers\Admin;
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Obra;

class ObraIndexController extends Controller
{
    // GET /api/admin/obras
    public function index()
    {
        $obras = Obra::query()
            ->select('id','nombre','codigo','created_at')
            ->orderBy('nombre')
            ->get();

        return response()->json($obras);
    }
}
