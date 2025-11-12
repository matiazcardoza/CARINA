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
use App\Models\Service;
use App\Models\WorkEvidence;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $dailyPart = DailyPart::create([
            'service_id' => $request->service_id,
            'shift_id' => ($request->shift_id === 'all') ? null : $request->shift_id,
            'operator_id' => $request->operator_id,
            //'itemPecosa_id' => $request->product_id,
            'work_date' => $request->work_date,
            'start_time' => date("H:i", strtotime($request->start_time)),
            'initial_fuel' => $request->initial_fuel,
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

            $dailyPart->update([
                'operator_id' => $request->operator_id,
                'start_time' =>date("H:i", strtotime($request->start_time)),
                'end_time' => date("H:i", strtotime($request->end_time)),
                'occurrences' => $request->occurrences,
                'work_date' => $request->work_date,
                //'itemPecosa_id' => $request->product_id,
                'initial_fuel' => $request->initial_fuel ?? null,
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
            $orderSilucia = OrderSilucia::where('orders_silucia.id', $service->order_id)
                            ->leftJoin('equipment_order', 'orders_silucia.id', '=', 'equipment_order.order_silucia_id')
                            ->select('orders_silucia.silucia_id',
                                    'orders_silucia.order_type',
                                    'orders_silucia.supplier',
                                    'orders_silucia.ruc_supplier',
                                    'orders_silucia.delivery_date',
                                    'orders_silucia.deadline_day',
                                    'equipment_order.order_silucia_id',
                                    'equipment_order.machinery_equipment',
                                    'equipment_order.ability',
                                    'equipment_order.brand',
                                    'equipment_order.model',
                                    'equipment_order.serial_number',
                                    'equipment_order.year',
                                    'equipment_order.plate',
                                    'orders_silucia.state'
                                    )->first();
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
        $qr_code = base64_encode("data_qr_example");

        $data = [
            'logoPath' => $logoPath,
            'logoWorkPath' => $logoWorkPath,
            'orderSilucia' => $orderSilucia,
            'mechanicalEquipment' => $mechanicalEquipment,
            'service' => $service,
            'dailyPart' => $dailyPart,
            'pdf' => true,
            'qr_code' => $qr_code
        ];

        $pdf = Pdf::loadView('pdf.daily_part', $data);
        $pdf->setPaper('A4', 'portrait');

        $directory = "daily_parts/{$serviceId}";
        $fileName = "daily_part{$request->shift_id}_{$request->date}.pdf";
        $filePath = "{$directory}/{$fileName}";

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        Storage::disk('public')->put($filePath, $pdf->output());
        $existingDocument = DocumentDailyPart::where('file_path', $filePath)->first();
        if (Auth::id() === 1) {
            if ($existingDocument) {
                $existingDocument->update([
                    'state' => 0
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
                    'state' => 0
                ]
            );
        }

        
        DailyPart::where('work_date', $request->date)
            ->where('service_id', $serviceId)
            ->where('shift_id', $request->shift_id)
            ->update([
                'document_id' => $document->id,
                'state' => 3
            ]);

        return response()->json([
            'message' => 'PDF generado y reemplazado correctamente',
            'data' => $dailyPart
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

    public function getdailyPartsPendings(Request $request){
        $numDoc = $request->query('dni');
        $mes = (int) $request->query('mes');
        $anio = (int) $request->query('anio');
        if ($mes < 1 || $mes > 12) {
            return response()->json(['error' => 'El mes debe estar entre 1 y 12.'], 400);
        }
        if ($anio < 1900 || $anio > 2100) {
            return response()->json(['error' => 'El año debe estar entre 1900 y 2100.'], 400);
        }

        Log::info("Ingreso a funcion getdailyPartsPendings con DNI: {$numDoc}, Mes: {$mes}, Año: {$anio}");

        // 2. Obtener la información de la Persona (Usuario)
        $persona = Persona::where('num_doc', $numDoc)->first();

        if (!$persona) {
            return response()->json(['error' => 'Persona no encontrada con el DNI proporcionado.'], 404);
        }

        $userId = $persona->user_id;

        // 3. Definir el rango de fechas (Del 15 del mes M al 15 del mes M+1)
        try {
            Log::info("Creando fechas para Mes: {$mes}, Año: {$anio}");
            
            $prevMonth = $mes - 1;
            $startDate = Carbon::create($anio, $prevMonth, 15, 0, 0, 0)->startOfDay();
            
            // Fecha de fin: 15 del mes siguiente
            $endDate = $startDate->copy()->addMonth()->startOfDay();

        } catch (\Exception $e) {
            Log::error("Error al crear fechas: " . $e->getMessage());
            return response()->json(['error' => 'Parámetros de fecha inválidos.'], 400);
        }

        // 4. Obtener todos los DailyParts (Partes Diarios) de esta persona en el rango de fechas
        // CORRECCIÓN: usar 'operator_id' y 'work_date' según la estructura real de la tabla
        $dailyParts = DailyPart::where('operator_id', $userId)
            ->whereBetween('work_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(function ($item) {
                // Claveamos la colección por la fecha para una búsqueda rápida (O(1))
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        // 5. Generar la lista de reportes para cada día del rango
        $reportes = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $reporteDelDia = $dailyParts->get($dateString);

            $detalle = 'Sin reportes creados';
            $reporteHoy = false;

            if ($reporteDelDia) {
                $status = $reporteDelDia->status;
                if ($status == 3) {
                    $reporteHoy = true;
                    $detalle = 'Reporte completado (Estado 3)';
                } else {
                    $detalle = "Pendiente: Estado $status";
                }
            }

            $reportes[] = [
                'fecha' => $dateString,
                'reporte_hoy' => $reporteHoy,
                'detalle' => $detalle,
            ];

            $currentDate->addDay();
        }

        // Calcular el total de días correctamente
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // 6. Construir la respuesta final JSON
        return response()->json([
            'dni' => $persona->num_doc,
            'user_id' => $persona->user_id,
            'nombre' => trim("{$persona->nombres} {$persona->apellido_paterno} {$persona->apellido_materno}"),
            'desde' => $startDate->format('Y-m-d'),
            'hasta' => $endDate->format('Y-m-d'),
            'total_dias' => $totalDays,
            'reportes' => $reportes,
        ]);
    }
}
