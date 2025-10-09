<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ItemPecosa;
use App\Models\Report;
class ProductController extends Controller
{
    function consultaProductSelect(Request $request){
        /*$products = ItemPecosa::select('id', 'numero', 'item')->get();
        return response()->json([
            'message' => 'productos cargados correctamente',
            'data' => $products
        ]);*/
    }
}
