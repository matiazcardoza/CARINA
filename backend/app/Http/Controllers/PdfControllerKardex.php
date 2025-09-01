<?php

namespace App\Http\Controllers;

// use BaconQrCode\Encoder\QrCode;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
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

    public function generateQRCcode(){
        // $filename = 'this is an example name';
        // $qrPng = QrCode::format('png')
        // ->size(300)           // tamaño del QR
        // ->margin(1)           // borde
        // ->errorCorrection('H')// robustez
        // ->generate($filename);
        // return $qrPng;
        // QrCode::format('png')->merge($rutaLogo,0.3)->size(300)->errorCorrection('H')->generate($url)
        $contenido = 'https://ejemplo.com/recurso?id=123';
        $qrPng = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($contenido);

        return response($qrPng)
            ->header('Content-Type', 'image/png');

    }
}
