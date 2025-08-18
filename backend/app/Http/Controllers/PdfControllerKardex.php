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

        $personas = $personas = [
    [
        'id' => '01',
        'nombre' => 'Juan Pérez',
        'email' => 'juan.perez@example.com',
        'telefono' => '987654321',
        'observations' => 'Solicitó ajuste de inventario por diferencia en conteo físico. Se recomienda revisar el historial de entradas.',
        'codigos_qr' => [
            'https://example.com/qr/01/inventario',
            'https://example.com/qr/01/firma'
        ]
    ],
    [
        'id' => '02',
        'nombre' => 'María Gómez',
        'email' => 'maria.gomez@example.com',
        'telefono' => '987654322',
        'observations' => 'Reportó salida no registrada en sistema. Se validó con guía de despacho N° 2041.',
        'codigos_qr' => []
    ],
    [
        'id' => '03',
        'nombre' => 'Carlos Torres',
        'email' => 'carlos.torres@example.com',
        'telefono' => '987654323',
        'observations' => 'Ingreso de producto con lote vencido. Se realizó devolución parcial.',
        'codigos_qr' => [
            'https://example.com/qr/03/devolucion'
        ]
    ],
    [
        'id' => '02',
        'nombre' => 'María Gómez',
        'email' => 'maria.gomez@example.com',
        'telefono' => '987654322',
        'observations' => 'Reportó salida no registrada en sistema. Se validó con guía de despacho N° 2041.',
        'codigos_qr' => []
    ],
    [
        'id' => '03',
        'nombre' => 'Carlos Torres',
        'email' => 'carlos.torres@example.com',
        'telefono' => '987654323',
        'observations' => 'Ingreso de producto con lote vencido. Se realizó devolución parcial.',
        'codigos_qr' => [
            'https://example.com/qr/03/devolucion'
        ]
    ],
    [
        'id' => '02',
        'nombre' => 'María Gómez',
        'email' => 'maria.gomez@example.com',
        'telefono' => '987654322',
        'observations' => 'Reportó salida no registrada en sistema. Se validó con guía de despacho N° 2041.',
        'codigos_qr' => []
    ],
    [
        'id' => '03',
        'nombre' => 'Carlos Torres',
        'email' => 'carlos.torres@example.com',
        'telefono' => '987654323',
        'observations' => 'Ingreso de producto con lote vencido. Se realizó devolución parcial.',
        'codigos_qr' => [
            'https://example.com/qr/03/devolucion'
        ]
    ],
    [
        'id' => '02',
        'nombre' => 'María Gómez',
        'email' => 'maria.gomez@example.com',
        'telefono' => '987654322',
        'observations' => 'Reportó salida no registrada en sistema. Se validó con guía de despacho N° 2041.',
        'codigos_qr' => []
    ],
    [
        'id' => '03',
        'nombre' => 'Carlos Torres',
        'email' => 'carlos.torres@example.com',
        'telefono' => '987654323',
        'observations' => 'Ingreso de producto con lote vencido. Se realizó devolución parcial.',
        'codigos_qr' => [
            'https://example.com/qr/03/devolucion'
        ]
    ],
    [
        'id' => '04',
        'nombre' => 'Lucía Fernández',
        'email' => 'lucia.fernandez@example.com',
        'telefono' => '987654324',
        'observations' => 'Movimiento duplicado detectado en Kardex. Se corrigió en sistema y se notificó al área de logística.',
        'codigos_qr' => []
    ]
];


        // loadView tiene dos parámetros uno de path y otro de datos
        $pdf = Pdf::loadView('pdfKardex.reporte', compact('personas'))->setPaper('a4', 'portrait');
        // $pdf = Pdf::loadView('pdfKardex.reporte');

        // para ver en el navegador
        // return $pdf->stream('reporte.pdf');

        // para descargar el pdf
        return $pdf->download("orden.pdf");
        // $pdf = Pdf::loadView('pdfKardex.reporte', compact('personas'))
        //   ->setPaper('a4', 'portrait');
    }
}
