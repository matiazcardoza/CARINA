<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProductMovementKardexController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        // /api/products/1/movements-kardex
        $movements = $product->movements()
            ->orderByDesc('movement_date')  // si no hay fecha, usa ->latest()
            ->orderByDesc('id')
            ->get();

        return response()->json($movements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    
    public function pdf(Product $product, Request $request){
        // return "hola mundo";
        // $nombre = $request->query('nombre', 'Invitado');
        // $nombre = "hola mundo";


        // filtros opcionales (?from=YYYY-MM-DD&to=YYYY-MM-DD&type=entrada|salida)
        $from = $request->query('from');
        $to   = $request->query('to');
        $type = $request->query('type'); // 'entrada' | 'salida'

        // Cargar la relación con filtros y orden
        $product->load(['movements' => function ($q) use ($from, $to, $type) {
            if ($from) $q->whereDate('movement_date', '>=', $from);
            if ($to)   $q->whereDate('movement_date', '<=', $to);
            if ($type) $q->where('movement_type', $type);

            $q->orderBy('movement_date', 'asc')
            ->select([
                'id',
                'product_id',
                'movement_date',
                'class',
                'number',
                'movement_type',
                'amount',
                'observations'
            ]);
        }]);


        // los movements son los movimiento kardex de cada producto
        $movements = $product->movements;
        $totalEntradas = $movements->where('movement_type','entrada')->sum('amount');
        $totalSalidas  = $movements->where('movement_type','salida')->sum('amount');
        $stockFinal    = $totalEntradas - $totalSalidas;

        $pdf_details = [
            'product'       => $product,
            'movements'     => $movements,
            'totalEntradas' => $totalEntradas,
            'totalSalidas'  => $totalSalidas,
            'stockFinal'    => $totalEntradas - $totalSalidas,
        ];
        // return $pdf_details;
        $pdf = Pdf::loadView('pdfKardex.reporte', compact('pdf_details'))->setPaper('a4', 'portrait');

        return $pdf->download("orden.pdf");

// ------------------------------------------------------------------
        return $items;

        $items = [
            [
                'id' => '01',
                'movement_date' => '01/08/2025',
                // datos de documento comprobante 
                'class' => 'FC',
                'number' => '0001',

                'movement_type' => 'entrada',
                'amount' => '150',
                
                'observations' => 'esta es la observacion dada para este movimiento',
                'qr_codes' => [

                ]
            ],
            [
                'id' => '01',
                'movement_date' => '01/08/2025',
                // datos de documento comprobante 
                'class' => 'FC',
                'number' => '0001',

                'movement_type' => 'entrada',
                'amount' => '150',
                
                'observations' => 'esta es la observacion dada para este movimiento',
                'qr_codes' => [
                    '1331234234234234234',
                    '1331234234234234234',
                    '1331234234234234234',
                    '1331234234234234234'
                ]
            ],            [
                'id' => '01',
                'movement_date' => '01/08/2025',
                // datos de documento comprobante 
                'class' => 'FC',
                'number' => '0001',

                'movement_type' => 'entrada',
                'amount' => '150',
                
                'observations' => 'esta es la observacion dada para este movimiento',
                'qr_codes' => [
                    '1331234234234234234',
                    '1331234234234234234',
                    '1331234234234234234',
                    '1331234234234234234'
                ]
            ],            [
                'id' => '01',
                'movement_date' => '01/08/2025',
                // datos de documento comprobante 
                'class' => 'FC',
                'number' => '0001',

                'movement_type' => 'entrada',
                'amount' => '150',
                
                'observations' => 'esta es la observacion dada para este movimiento',
                'qr_codes' => [
                    '1331234234234234234',
                    '1331234234234234234',
                    '1331234234234234234',
                    '1331234234234234234'
                ]
            ],

        ];


        // loadView tiene dos parámetros uno de path y otro de datos
        $pdf = Pdf::loadView('pdfKardex.reporte', compact('items'))->setPaper('a4', 'portrait');
        // $pdf = Pdf::loadView('pdfKardex.reporte');

        // para ver en el navegador
        // return $pdf->stream('reporte.pdf');

        // para descargar el pdf
        return $pdf->download("orden.pdf");
        // $pdf = Pdf::loadView('pdfKardex.reporte', compact('personas'))
        //   ->setPaper('a4', 'portrait');
    }

}
