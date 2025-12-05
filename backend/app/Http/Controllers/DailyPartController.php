<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\ItemPecosa;
use App\Models\MechanicalEquipment;
use App\Models\MovementKardex;
use App\Models\OrderSilucia;
use App\Models\Persona;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\WorkEvidence;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Smalot\PdfParser\Parser;

class DailyPartController extends Controller
{
    function index(Request $request)
    {
        $serviceId = $request->id;
        $date = $request->query('date', now()->format('Y-m-d'));
        $dailyParts = DailyPart::select('daily_parts.*', 'operators.name as operator')
            ->leftJoin('operators', 'daily_parts.operator_id', '=', 'operators.id')
            ->whereDate('work_date', $date)
            ->where('shift_id', $request->shift_id)
            ->where('daily_parts.service_id', $serviceId)
            ->get();
        /*$dailyParts = DailyPart::select('daily_parts.*', 'item_pecosas.numero', 'item_pecosas.item')
            ->whereDate('work_date', $date)
            ->where('service_id', $serviceId)
            ->leftJoin('item_pecosas', 'item_pecosas.id', '=', 'daily_parts.itemPecosa_id')
            ->get();*/

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $dailyParts
        ]);
    }

    function store(Request $request)
    {
        $lastRecord = DailyPart::whereYear('work_date', date('Y'))
                    ->whereMonth('work_date', date('m'))
                    ->orderBy('num_reg', 'desc')
                    ->first();

        $newNumReg = $lastRecord ? $lastRecord->num_reg + 1 : 1;

        $dailyPart = DailyPart::create([
            'service_id' => $request->service_id,
            'shift_id' => ($request->shift_id === 'all') ? null : $request->shift_id,
            'operator_id' => $request->operator_id,
            //'itemPecosa_id' => $request->product_id,
            'num_reg' => $newNumReg,
            'work_date' => $request->work_date,
            'start_time' => date("H:i", strtotime($request->start_time)),
            'initial_fuel' => $request->initial_fuel,
            'gasolina' => $request->gasoline_amount,
            'description' => $request->description
        ]);

        $servicio = Service::find($request->service_id);
        if($request->initial_fuel){
            $servicio->update([
                'fuel_consumed' => $servicio->fuel_consumed + $request->initial_fuel
            ]);

            /*$product = ItemPecosa::find($request->product_id);

            $product->update([
                'quantity_issued' => $request->initial_fuel,
                'quantity_on_hand' => $product->stock_qty - $request->initial_fuel,
                'last_movement_at'=> now(),
            ]);

            $MovementKardex = MovementKardex::create([
                'item_pecosa_id' => $product->id,
                'movement_type' => 'salida',
                'movement_date' => now(),
                'amount' => $request->initial_fuel,
                'observations' => 'salida a parte diaria'
            ]);

            $dailyPart->update([
                'movement_kardex_id' => $MovementKardex->id
            ]);*/
        }

        return response()->json([
            'message' => 'Daily work log created successfully',
            'data' => $dailyPart
        ], 201);
    }

    public function update(Request $request)
    {
        $dailyPart = DailyPart::findOrFail($request->id);

        $service = Service::find($request->service_id);

        if ($request->initial_fuel || $service->state === 3 || $service->state === 1 || $service->state === 2) {
            /*if ($dailyPart->itemPecosa_id != $request->product_id) {
                $prevProduct = ItemPecosa::find($dailyPart->itemPecosa_id);
                $prevProduct->update([
                    'quantity_received' => $dailyPart->initial_fuel,
                    'quantity_on_hand' => $prevProduct->stock_qty + $dailyPart->initial_fuel
                ]);
            }*/

            /*$product = ItemPecosa::find($request->product_id);
            $diferentFuel = $request->initial_fuel - $dailyPart->initial_fuel;
            $product->update([
                'quantity_issued' => $request->initial_fuel,
                'quantity_on_hand' => $product->stock_qty + $diferentFuel
            ]);

            $diferentFuelService = $request->initial_fuel - $dailyPart->initial_fuel;
            $servicio = Service::find($dailyPart->service_id);
            $servicio->update([
                'fuel_consumed' => $servicio->fuel_consumed + $diferentFuelService
            ]);*/

            $workDate = $request->work_date;
            $startTime = date("H:i:s", strtotime($request->start_time));
            $endTime = date("H:i:s", strtotime($request->end_time));

            $startDateTime = Carbon::parse($workDate . ' ' . $startTime);
            $endDateTime = Carbon::parse($workDate . ' ' . $endTime);

            if ($endDateTime->lessThan($startDateTime)) {
                $endDateTime->addDay();
            }

            $diffInSeconds = $endDateTime->diffInSeconds($startDateTime, true);
            $workedTime = gmdate('H:i', $diffInSeconds);

            $dailyPart->update([
                'operator_id' => $request->operator_id,
                'start_time' =>date("H:i", strtotime($request->start_time)),
                'end_time' => date("H:i", strtotime($request->end_time)),
                'time_worked' => $workedTime,
                'occurrences' => $request->occurrences,
                'work_date' => $request->work_date,
                //'itemPecosa_id' => $request->product_id,
                'initial_fuel' => $request->initial_fuel ?? null,
                'gasolina' => $request->gasoline_amount ?? null,
                'description' => $request->description
            ]);

            /*$MovementKardex = MovementKardex::find($dailyPart->movement_kardex_id);
            $MovementKardex->update([
                'item_pecosa_id' => $request->product_id,
                'amount' => $request->initial_fuel
            ]);*/
        } else {
            /*$dailyPart->update([
                'itemPecosa_id' => $request->product_id,
                'description' => $request->description
            ]);*/
        }
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

    public function destroyService($id)
    {
        $service = Service::findOrFail($id);
        $orderSilucia = OrderSilucia::find($service->order_id);
        $service->delete();
        if($orderSilucia){
            $orderSilucia->delete();
        }

        return response()->json([
            'message' => 'Daily work log deleted successfully'
        ], 204);
    }

    public function completeWork(Request $request)
    {
        $dailyPart = DailyPart::find($request->workLogId);
        $endTime = date("H:i", strtotime($request->end_time));

        $workDate = $dailyPart->work_date;
        $startTimeOnly = date("H:i:s", strtotime($dailyPart->start_time));
        $startDateTime = Carbon::parse($workDate . ' ' . $startTimeOnly);
        $endDateTime = Carbon::parse($workDate . ' ' . $endTime);

        if ($endDateTime->lessThan($startDateTime)) {
            $endDateTime->addDay();
        }

        $diffInSeconds = $endDateTime->diffInSeconds($startDateTime, true);
        $workedTime = gmdate('H:i:s', $diffInSeconds);

        $dailyPart->update([
            'end_time'    => $endTime,
            'occurrences' => $request->occurrence,
            'time_worked' => $workedTime,
            'state'       => 2
        ]);

        if ($request->hasFile('images')) {
            $serviceId = $request->serviceId;
            $directory = "work_evidences/{$serviceId}";
            foreach ($request->file('images') as $index => $image) {
                $timestamp = now()->format('YmdHis');
                $extension = $image->getClientOriginalExtension();
                $fileName = "{$dailyPart->id}_evidence_{$index}_{$timestamp}.{$extension}";

                $path = $image->storeAs($directory, $fileName, 'public');

                WorkEvidence::create([
                    'daily_part_id' => $dailyPart->id,
                    'evidence_path' => $path
                ]);
            }
        }

        return response()->json([
            'message' => 'Daily work log completed successfully',
        ], 200);
    }

    public function generatePdf(Request $request, $serviceId)
    {
        $service = Service::findOrFail($serviceId);

        $orderSilucia = null;
        $mechanicalEquipment = null;

        if($service->order_id){
            $orderSilucia = DB::table('orders_silucia')
                ->where('orders_silucia.id', $service->order_id)
                ->select(
                    'orders_silucia.silucia_id',
                    'orders_silucia.order_type',
                    'orders_silucia.supplier',
                    'orders_silucia.ruc_supplier',
                    'orders_silucia.delivery_date',
                    'orders_silucia.deadline_day',
                    'orders_silucia.state'
                )
                ->first();
            if ($service->equipment_order_id) {
                $equipment = DB::table('equipment_order')
                    ->where('id', $service->equipment_order_id)
                    ->select(
                        'machinery_equipment',
                        'ability',
                        'brand',
                        'model',
                        'serial_number',
                        'year',
                        'plate'
                    )
                    ->first();
                if ($equipment) {
                    foreach ($equipment as $key => $value) {
                        $orderSilucia->{$key} = $value;
                    }
                }
            }
        }else{
            $mechanicalEquipment = MechanicalEquipment::find($service->mechanical_equipment_id);
        }

        $dailyPart = DailyPart::select('daily_parts.*', 'operators.name as operator')
            ->where('work_date', $request->date)
            ->leftJoin('operators', 'operators.id', '=', 'daily_parts.operator_id')
            ->where('daily_parts.service_id', $serviceId)
            ->where('shift_id', $request->shift_id)
            ->get();

        $logoPath = storage_path('app/public/image_pdf_template/logo_grp.png');
        $logoWorkPath = storage_path('app/public/image_pdf_template/logo_work.png');
        $directory = "daily_parts/{$serviceId}";
        $fileName = "daily_part{$request->shift_id}_{$request->date}.pdf";
        $filePath = "{$directory}/{$fileName}";
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        $timestamp = time();
        $baseUrl = config('app.url');
        $documentUrl = $baseUrl . Storage::url($filePath) . '?v=' . $timestamp;
        $qrCode = null;
        try {
            $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&format=png&data=' . urlencode($documentUrl);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true,
                    'header' => "User-Agent: Mozilla/5.0\r\n"
                ]
            ]);
            $qrImageData = @file_get_contents($qrApiUrl, false, $context);

            if ($qrImageData !== false && strlen($qrImageData) > 0) {
                $qrCode = base64_encode($qrImageData);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo generar QR Code: ' . $e->getMessage());
        }

        $data = [
            'logoPath' => $logoPath,
            'logoWorkPath' => $logoWorkPath,
            'orderSilucia' => $orderSilucia,
            'mechanicalEquipment' => $mechanicalEquipment,
            'service' => $service,
            'dailyPart' => $dailyPart,
            'pdf' => true,
            'qr_code' => $qrCode,
            'document_url' => $documentUrl
        ];

        $pdf = Pdf::loadView('pdf.daily_part', $data);
        $pdf->setPaper('A4', 'portrait');

        if (Storage::disk('public')->exists($filePath)) {
            try {
                Storage::disk('public')->delete($filePath);
                usleep(100000);
            } catch (\Exception $e) {
                Log::warning('No se pudo eliminar archivo anterior: ' . $e->getMessage());
            }
        }

        try {
            Storage::disk('public')->put($filePath, $pdf->output());
        } catch (\Exception $e) {
            Log::error('Error guardando PDF: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al generar el PDF',
                'error' => $e->getMessage()
            ], 500);
        }
        try {
            $existingDocument = DocumentDailyPart::where('file_path', $filePath)->first();

            if (Auth::id() === 1) {
                if ($existingDocument) {
                    $existingDocument->update([
                        'state' => 0,
                        'updated_at' => now()
                    ]);
                    $document = $existingDocument;
                } else {
                    $document = DocumentDailyPart::create([
                        'user_id' => Auth::id(),
                        'user_id_send' => Auth::id(),
                        'file_path' => $filePath,
                        'state' => 0
                    ]);
                }
            } else {
                $document = DocumentDailyPart::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'file_path' => $filePath,
                    ],
                    [
                        'state' => 0,
                        'updated_at' => now()
                    ]
                );
            }

            DailyPart::where('work_date', $request->date)
                ->where('service_id', $serviceId)
                ->where('shift_id', $request->shift_id)
                ->update([
                    'document_id' => $document->id,
                    'state' => 3,
                    'updated_at' => now()
                ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando base de datos: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'PDF generado y reemplazado correctamente',
            'data' => $dailyPart,
            'document_url' => url(Storage::url($filePath)),
            'document_path' => $filePath,
            'qr_generated' => $qrCode !== null
        ], 201);
    }

    public function getDocumentWokLog($serviceId, $date, $shift){
        Log::info("serviceId: $serviceId, date: $date, shift: $shift");
        $dailyPart = DailyPart::where('service_id', $serviceId)->where('work_date', $date)->where('shift_id', ($shift === 'all') ? null : $shift)->first();
        Log::info('esta es la  salida de parte diaria: ' . $dailyPart);
        $document = DocumentDailyPart::find($dailyPart->document_id);

        $parser = new Parser();
        $pdf = $parser->parseFile(storage_path('app/public/' . $document->file_path));
        $numPages = count($pdf->getPages());

        return response()->json([
            'message' => 'get document completed successfully',
            'data' => $document,
            'pages' => $numPages
        ], 201);
    }

    public function getDailyPartsPendings(Request $request)
    {
        $numDoc = $request->query('dni');
        $mes = (int) $request->query('mes');
        $anio = (int) $request->query('anio');
        if ($mes < 1 || $mes > 12) {
            return response()->json(['error' => 'El mes debe estar entre 1 y 12.'], 400);
        }
        if ($anio < 1900 || $anio > 2100) {
            return response()->json(['error' => 'El año debe estar entre 1900 y 2100.'], 400);
        }
        $persona = Persona::where('num_doc', $numDoc)->first();
        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada con el DNI proporcionado.'], 404);
        }

        $userId = $persona->user_id;
        try {
            $prevMonth = $mes - 1;
            $startDate = Carbon::create($anio, $prevMonth, 15, 0, 0, 0)->startOfDay();
            $endDate = $startDate->copy()->addMonth()->subDay()->endOfDay();
        } catch (\Exception $e) {
            Log::error("Error al crear fechas: " . $e->getMessage());
            return response()->json(['error' => 'Parámetros de fecha inválidos.'], 400);
        }
        $goals = Project::where('user_id', $userId)->get();

        if ($goals->isEmpty()) {
            return response()->json([
                'dni' => $persona->num_doc,
                'user_id' => $persona->user_id,
                'nombre' => trim("{$persona->name} {$persona->last_name}"),
                'desde' => $startDate->format('Y-m-d'),
                'hasta' => $endDate->format('Y-m-d'),
                'total_dias' => $startDate->diffInDays($endDate) + 1,
                'mensaje' => 'El usuario no tiene proyectos asignados',
                'reportes' => [],
            ]);
        }
        $projectIds = $goals->pluck('goal_id')->unique();
        $services = Service::whereIn('goal_id', $projectIds)->get();

        if ($services->isEmpty()) {
            return response()->json([
                'dni' => $persona->num_doc,
                'user_id' => $persona->user_id,
                'nombre' => trim("{$persona->name} {$persona->last_name}"),
                'desde' => $startDate->format('Y-m-d'),
                'hasta' => $endDate->format('Y-m-d'),
                'total_dias' => $startDate->diffInDays($endDate) + 1,
                'mensaje' => 'No hay servicios asociados a los proyectos del usuario',
                'reportes' => [],
            ]);
        }
        $serviceIds = $services->pluck('id');
        $dailyParts = DailyPart::with('document')
            ->whereIn('service_id', $serviceIds)
            ->whereBetween('work_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });
        $reportes = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $partesDelDia = $dailyParts->get($dateString);

            $detalle = 'Sin reportes creados';
            $reporteHoy = false;
            $documentoCompletado = false;

            if ($partesDelDia && $partesDelDia->isNotEmpty()) {
                // Verificar si existe al menos un parte diario con documento en estado 3
                $tieneDocumentoCompletado = $partesDelDia->filter(function($parte) {
                    return $parte->document && $parte->document->state == 3;
                })->isNotEmpty();

                if ($tieneDocumentoCompletado) {
                    $reporteHoy = true;
                    $documentoCompletado = true;
                    $detalle = 'Documento completado y aprobado';
                } else {
                    // Verificar si hay documentos en otros estados
                    $estadosDocumentos = $partesDelDia->filter(function($parte) {
                        return $parte->document;
                    })->pluck('document.state')->unique();

                    if ($estadosDocumentos->isNotEmpty()) {
                        $estados = $estadosDocumentos->sort()->implode(', ');
                        $detalle = "Documentos pendientes (Estados: $estados)";
                        $reporteHoy = true;
                    } else {
                        $detalle = 'Partes diarios sin documento generado';
                        $reporteHoy = true;
                    }
                }
            }

            $reportes[] = [
                'fecha' => $dateString,
                'reporte_hoy' => $reporteHoy,
                'documento_completado' => $documentoCompletado,
                'detalle' => $detalle,
                'cantidad_partes' => $partesDelDia ? $partesDelDia->count() : 0,
            ];

            $currentDate->addDay();
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $diasConReportes = collect($reportes)->where('reporte_hoy', true)->count();
        $diasCompletados = collect($reportes)->where('documento_completado', true)->count();
        $diasPendientes = $diasConReportes - $diasCompletados;

        return response()->json([
            'dni' => $persona->num_doc,
            'user_id' => $persona->user_id,
            'nombre' => trim("{$persona->name} {$persona->last_name}"),
            'desde' => $startDate->format('Y-m-d'),
            'hasta' => $endDate->format('Y-m-d'),
            'total_dias' => $totalDays,
            'estadisticas' => [
                'dias_con_reportes' => $diasConReportes,
                'dias_completados' => $diasCompletados,
                'dias_pendientes' => $diasPendientes,
                'dias_sin_reportes' => $totalDays - $diasConReportes,
            ],
            'proyectos_asignados' => $projectIds->values(),
            'reportes' => $reportes,
        ]);
    }
}
