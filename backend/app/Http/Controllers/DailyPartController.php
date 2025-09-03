<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
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

class DailyPartController extends Controller
{
    function index(Request $request)
    {
        $serviceId = $request->id;
        $dailyParts = DailyPart::select('daily_parts.*', 'products.numero', 'products.item')
            ->where('service_id', $serviceId)
            ->leftJoin('products', 'products.id', '=', 'daily_parts.products_id')
            ->get();

        return response()->json([
            'message' => 'Daily work log retrieved successfully',
            'data' => $dailyParts
        ]);
    }

    function store(Request $request)
    {
        $dailyPart = DailyPart::create([
            'service_id' => $request->service_id,
            'products_id' => $request->product_id,
            'work_date' => $request->work_date,
            'start_time' => $request->start_time,
            'initial_fuel' => $request->initial_fuel,
            'description' => $request->description
        ]);

        $product = Product::find($request->product_id);
        
        $product->update([
            'out_qty' => $request->initial_fuel,
            'stock_qty' => $product->stock_qty - $request->initial_fuel,
            'last_movement_at'=> now(),
        ]);

        MovementKardex::create([
            'product_id' => $product->id,
            'movement_type' => 'salida',
            'movement_date' => now(),
            'amount' => $request->initial_fuel,
            'observations' => 'salida a parte diaria'
        ]);

        return response()->json([
            'message' => 'Daily work log created successfully',
            'data' => $dailyPart
        ], 201);
    }

    public function update(Request $request)
    {
        $dailyPart = DailyPart::findOrFail($request->id);

        $product = Product::find($request->product_id);
        $diferentFuel = $request->initial_fuel - $dailyPart->initial_fuel;

        if(){
            
        }

        $product->update([
            'out_qty' => $request->initial_fuel,
            'stock_qty' => $product->stock_qty + $diferentFuel
        ]);

        $dailyPart->update([
            'products_id' => $request->product_id,
            'initial_fuel' => $request->initial_fuel,
            'description' => $request->description
        ]);

        $MovementKardex = MovementKardex::where('product_id', $product->id)->first();

        $MovementKardex->update([
            'product_id' => $product->id,
            'amount' => $request->initial_fuel
        ]);

        return response()->json([
            'message' => 'Daily work log actualised successfully',
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

    public function generatePdf($serviceId)
    {

        $service = Service::find($serviceId);
        $orderSilucia = OrderSilucia::find($service->order_id);
        $dailyPart = DailyPart::where('service_id', $serviceId)->get();
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

        return $pdf->stream('anexo_01_planilla.pdf');
    }
}
