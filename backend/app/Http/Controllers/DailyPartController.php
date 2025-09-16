<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\ItemPecosa;
use App\Models\MechanicalEquipment;
use App\Models\MovementKardex;
use App\Models\OrderSilucia;
use App\Models\Product;
use App\Models\Service;
use App\Models\WorkEvidence;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DailyPartController extends Controller
{
    function index(Request $request)
    {
        $serviceId = $request->id;
        $date = $request->query('date', now()->format('Y-m-d'));
        $dailyParts = DailyPart::select('daily_parts.*', 'item_pecosas.numero', 'item_pecosas.item')
            ->whereDate('work_date', $date)
            ->where('service_id', $serviceId)
            ->leftJoin('item_pecosas', 'item_pecosas.id', '=', 'daily_parts.itemPecosa_id')
            ->get();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $dailyParts
        ]);
    }

    function store(Request $request)
    {
        Log::info('request', $request->all());
        $dailyPart = DailyPart::create([
            'service_id' => $request->service_id,
            'itemPecosa_id' => $request->product_id,
            'work_date' => $request->work_date,
            'start_time' => $request->start_time,
            'initial_fuel' => $request->initial_fuel,
            'description' => $request->description
        ]);

        $servicio = Service::find($request->service_id);
        $servicio->update([
            'fuel_consumed' => $servicio->fuel_consumed + $request->initial_fuel
        ]);

        $product = ItemPecosa::find($request->product_id);
        
        $product->update([
            'quantity_issued' => $request->initial_fuel,
            'quantity_on_hand' => $product->stock_qty - $request->initial_fuel,
            'last_movement_at'=> now(),
        ]);

        if($request->initial_fuel){
            $MovementKardex = MovementKardex::create([
                'item_pecosa_id' => $product->id,
                'movement_type' => 'salida',
                'movement_date' => now(),
                'amount' => $request->initial_fuel,
                'observations' => 'salida a parte diaria'
            ]);

            $dailyPart->update([
                'movement_kardex_id' => $MovementKardex->id
            ]);
        }

        return response()->json([
            'message' => 'Daily work log created successfully',
            'data' => $dailyPart
        ], 201);
    }

    public function update(Request $request)
    {
        $dailyPart = DailyPart::findOrFail($request->id);

        if($dailyPart->products_id != $request->product_id){
            $prevProduct = ItemPecosa::find($dailyPart->products_id);

            $prevProduct->update([
                'quantity_received' => $dailyPart->initial_fuel,
                'quantity_on_hand' => $prevProduct->stock_qty + $dailyPart->initial_fuel
            ]);
        } else {
            $product = ItemPecosa::find($request->product_id);
            $diferentFuel = $request->initial_fuel - $dailyPart->initial_fuel;
            $product->update([
                'quantity_issued' => $request->initial_fuel,
                'quantity_on_hand' => $product->stock_qty + $diferentFuel
            ]);
        }

        $diferentFuelService = $request->initial_fuel - $dailyPart->initial_fuel;
        
        $servicio = Service::find($dailyPart->service_id);
        $servicio->update([
            'fuel_consumed' => $servicio->fuel_consumed + $diferentFuelService
        ]);

        $dailyPart->update([
            'itemPecosa_id' => $request->product_id,
            'initial_fuel' => $request->initial_fuel,
            'description' => $request->description
        ]);

        $MovementKardex = MovementKardex::find($dailyPart->movement_kardex_id);
        $MovementKardex->update([
            'item_pecosa_id' => $request->product_id,
            'amount' => $request->initial_fuel
        ]);

        return response()->json([
            'message' => 'Daily work log successfully',
            'data' => $dailyPart
        ], 201);
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
        $dailyPart = DailyPart::find($request->workLogId);

        $start = Carbon::createFromFormat('H:i:s', $dailyPart->start_time);
        $end = Carbon::createFromFormat('H:i', $request->end_time);

        if ($end->lessThan($start)) {
            $end->addDay(); 
        }

        $diffInSeconds = $end->diffInSeconds($start);
        $workedTime = gmdate('H:i:s', $diffInSeconds);

        $dailyPart->update([
            'end_time'    => $request->end_time,
            'occurrences' => $request->occurrence,
            'time_worked' => $workedTime,
            'state'       => 2
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $timestamp = now()->format('YmdHis');
                $extension = $image->getClientOriginalExtension();
                $fileName = "{$dailyPart->id}_evidence_{$index}_{$timestamp}.{$extension}";
                
                $path = $image->storeAs('work_evidences', $fileName, 'public');
                
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
        $service = Service::find($serviceId);
        $orderSilucia = OrderSilucia::find($service->order_id);
        $dailyPart = DailyPart::where('work_date', $request->date)
                ->where('service_id', $serviceId)->get();
        $mechanicalEquipment = MechanicalEquipment::find($service->mechanical_equipment_id);
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
        $fileName = "daily_part_{$request->date}.pdf";
        $filePath = "{$directory}/{$fileName}";

        Storage::disk('public')->put($filePath, $pdf->output());

        $document = DocumentDailyPart::firstOrCreate(
            ['file_path' => $filePath],
            ['state' => 1]
        );
        
        $dailypart = DailyPart::where('work_date', $request->date);
        $dailypart->update([
            'document_id' => $document->id,
            'state' => 3
        ]);

        return response()->json([
            'message' => 'Save pdf completed successfully',
            'data' => $dailyPart
        ], 201);
    }

    public function getDocumentWokLog($serviceId){
        $dailyPart = DailyPart::where('service_id', $serviceId)->first();
        $document = DocumentDailyPart::find($dailyPart->document_id);
        return response()->json([
            'message' => 'get document completed successfully',
            'data' => $document
        ], 201);
    }
}
