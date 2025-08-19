<?php

namespace App\Http\Controllers;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfControllerKardex extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

    public function generar(Request $request)
    {   
        // return "hola mundo";
        // $nombre = $request->query('nombre', 'Invitado');
        // $nombre = "hola mundo";

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
