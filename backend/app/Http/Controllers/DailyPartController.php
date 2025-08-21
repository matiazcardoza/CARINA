<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyPartController extends Controller
{
    function index(Request $request)
    {
        $serviceId = $request->id;
        $dailyParts = DailyPart::where('service_id', $serviceId)->get();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $dailyParts
        ]);
    }

    function store(Request $request)
    {
        Log::info('estos son los datos index', ['request' => $request->all()]);

        $dailyPart = DailyPart::create([
            'service_id' => $request->work_log_id,
            'work_date' => $request->work_date,
            'start_time' => $request->start_time,
            'initial_fuel' => $request->initial_fuel,
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Daily work log created successfully',
            'data' => $dailyPart
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $dailyPart = DailyPart::findOrFail($id);

            $validatedData = $request->validate([
                'work_date' => 'required|date',
                'start_time' => 'required|date_format:H:i:s',
                'initial_fuel' => 'nullable|numeric|min:0',
            ]);

            if (isset($validatedData['start_time'])) {
                $time = $validatedData['start_time'];
                if (substr_count($time, ':') === 1) {
                    $validatedData['start_time'] = $time . ':00';
                }
            }

            $dailyPart->refresh();

            return response()->json([
                'message' => 'Daily work log updated successfully',
                'data' => $dailyPart
            ], 200);
    }

    public function destroy($id)
    {
        $dailyPart = DailyPart::findOrFail($id);
        $dailyPart->delete();

        return response()->json([
            'message' => 'Daily work log deleted successfully'
        ], 204);
    }

    public function completeWork(Request $request)
    {
        $worlkLogId = $request->workLogId;        
            $dailyPart = DailyPart::find($worlkLogId);
            $dailyPart->end_time = $request->end_time;
            $dailyPart->final_fuel = $request->final_fuel;
            $start = Carbon::parse($dailyPart->start_time);
            $end = Carbon::parse($dailyPart->end_time);
            $interval = $start->diff($end);
            $hours = $interval->h;
            $minutes = $interval->i;
            $timeWorked = $hours + ($minutes / 60);
            $dailyPart->time_worked = $timeWorked;
            $dailyPart->fuel_consumed = $dailyPart->final_fuel - $dailyPart->initial_fuel;
        $dailyPart->save();

        $service = Service::find($dailyPart->service_id);
        $service->occurrences = $request->occurrence;
        $service->save();



        return response()->json([
            'message' => 'Daily work log completed successfully',
            'data' => $dailyPart
        ], 200);
    }

    public function generatePdf($id)
    {
        $dailyPartData = [
            'id' => $id,
            'fecha_parte' => Carbon::now()->format('d/m/Y'),
            'servicio' => [
                'nombre' => 'EXCAVADORA CAT 320D',
                'codigo' => 'EXC-001',
                'operador' => 'Juan Carlos Mamani',
                'proyecto' => 'CONSTRUCCIÓN CARRETERA PUNO-JULIACA KM 15+000',
            ],
            'horario' => [
                'hora_inicio' => '07:00',
                'hora_fin' => '17:00',
                'horas_trabajadas' => 10.0,
                'horas_efectivas' => 9.5,
            ],
            'combustible' => [
                'inicial' => 85.5,
                'final' => 45.2,
                'consumido' => 40.3,
                'rendimiento' => 4.24,
            ],
            'actividades' => [
                'Excavación de cunetas laterales - Progresiva 15+000 a 15+500',
                'Conformación de taludes en corte',
                'Limpieza y mantenimiento de equipo',
                'Traslado de material excedente'
            ],
            'observaciones' => 'Trabajo ejecutado según especificaciones técnicas. Condiciones climáticas favorables.',
            'firmas' => [
                [
                    'nivel' => 'Controlador',
                    'nombre' => 'Ing. Carlos Quispe',
                    'fecha' => Carbon::now()->format('d/m/Y H:i'),
                    'estado' => 'firmado'
                ],
                [
                    'nivel' => 'Residente',
                    'nombre' => 'Ing. María Condori',
                    'fecha' => Carbon::now()->addHours(2)->format('d/m/Y H:i'),
                    'estado' => 'firmado'
                ],
                [
                    'nivel' => 'Supervisor',
                    'nombre' => 'Ing. Pedro Mamani',
                    'fecha' => null,
                    'estado' => 'pendiente'
                ]
            ],
            'evidencias' => [
                'Foto del área de trabajo inicial',
                'Foto del avance al 50%',
                'Foto del trabajo terminado',
                'Foto del equipo al final de jornada'
            ]
        ];

        // Ruta del logo usando storage publicado
        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $logoWorkPath = storage_path('app/public/image_pdf_template/logo_work.png');
    
        // Datos de la empresa y reporte
        $reportData = [
            'logo_empresa' => $logoPath,
            'logo_trabajo' => $logoWorkPath,
            'nombre_empresa' => 'GOBIERNO REGIONAL PUNO',
            'ruc_empresa' => '20448978280',
            'proyecto' => 'MEJORAMIENTO CARRETERA PUNO - JULIACA',
            'contrato' => 'N° 2024-001-GRP',
            'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            'usuario_genera' => 'Sistema Administrativo',
            'periodo' => Carbon::now()->format('F Y'), // Enero 2025, etc.
            'area' => 'GERENCIA DE INFRAESTRUCTURA'
        ];

        // Datos del personal (simulando datos de planilla)
        $personal = [
            [
                'orden' => 1,
                'apellido_paterno' => 'MAMANI',
                'apellido_materno' => 'QUISPE',
                'nombres' => 'JUAN CARLOS',
                'cargo' => 'OPERADOR DE MAQUINARIA',
                'remuneracion' => 2500.00,
                'fecha_ingreso' => '2023-01-15',
                'fecha_nacimiento' => '1985-05-20',
                'documento' => '12345678'
            ],
            [
                'orden' => 2,
                'apellido_paterno' => 'CONDORI',
                'apellido_materno' => 'MAMANI',
                'nombres' => 'MARIA ELENA',
                'cargo' => 'INGENIERA RESIDENTE',
                'remuneracion' => 4500.00,
                'fecha_ingreso' => '2022-03-10',
                'fecha_nacimiento' => '1980-12-15',
                'documento' => '87654321'
            ],
            [
                'orden' => 3,
                'apellido_paterno' => 'QUISPE',
                'apellido_materno' => 'HUANCA',
                'nombres' => 'PEDRO LUIS',
                'cargo' => 'SUPERVISOR DE OBRA',
                'remuneracion' => 3800.00,
                'fecha_ingreso' => '2023-07-01',
                'fecha_nacimiento' => '1978-09-08',
                'documento' => '45678912'
            ]
        ];

        // Generar QR code (ejemplo básico)
        $qr_data = "Verificar en: https://regionpuno.gob.pe/verify?id=" . $id;
        $qr_code = base64_encode("data_qr_example"); // Aquí deberías usar una librería para generar el QR real

        $data = [
            'dailyPartData' => $dailyPartData,
            'reportData' => $reportData,
            'personal' => $personal,
            'pdf' => true,
            'excel' => false,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.daily_part', $data);
        
        // Configurar opciones del PDF si es necesario
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->stream('anexo_01_planilla.pdf');
    }
}
