<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\FuelOrder;
use App\Models\Report;
use App\Models\SignatureEvent;
use App\Models\SignatureFlow;
use App\Models\SignatureStep;
use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;  
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use FPDF;
class FuelOrderController extends Controller
{
    /**
     * GET /api/fuel-orders
     * - Chofer: ve SOLO sus órdenes (propias).
     * - Supervisor: ve pendientes de supervisor (supervisor_status = null).
     * - Jefe: ve pendientes de jefe (supervisor_status = 'approved' y manager_status = null).
     * Puedes pasar ?all=1 para ver todo (solo para supervisor/jefe).
     */
    public function indexv01(Request $request)
    {
        $user = $request->user();
        $role = $user->role ?? 'chofer';

        $q = FuelOrder::query()->with(['driver:id,name', 'vehicle:id,plate,brand']);

        $all = (bool)$request->boolean('all', false);

        if ($role === 'chofer') {
            $q->where('driver_id', $user->id);
        } elseif ($role === 'supervisor') {
            if (!$all) {
                $q->whereNull('supervisor_status');
            }
        } elseif ($role === 'jefe') {
            if (!$all) {
                $q->where('supervisor_status', 'approved')
                  ->whereNull('manager_status');
            }
        }

        // Filtros opcionales
        if ($request->filled('numero')) {
            $q->where('numero', 'like', '%'.$request->string('numero').'%');
        }
        if ($request->filled('placa')) {
            $q->where(function ($qq) use ($request) {
                $placa = $request->string('placa');
                $qq->where('vehiculo_placa', 'like', '%'.$placa.'%')
                   ->orWhereHas('vehicle', fn($v) => $v->where('plate', 'like', '%'.$placa.'%'));
            });
        }

        return $q->orderByDesc('id')->paginate(15);
    }

    public function index(Request $req)
    {
        $perPage = (int) $req->query('rows', $req->query('per_page', 15));
        $numero  = $req->query('numero');
        $placa   = $req->query('placa');
        $all     = filter_var($req->query('all', false), FILTER_VALIDATE_BOOLEAN);

        $q = FuelOrder::query()
            ->with([
                'vehicle:id,plate',
                // Trae el último reporte + flujo (si existe)
                'latestFuelReport.flow.steps:id,signature_flow_id,order,role,status,page,pos_x,pos_y,width,height,callback_token'
            ])
            ->orderByDesc('id');

        if ($numero) { $q->where('numero', 'like', "%{$numero}%"); }
        if ($placa)  { 
            // usa snapshot o relación vehicle
            $q->where(function($qq) use ($placa) {
                $qq->where('vehiculo_placa', 'like', "%{$placa}%")
                   ->orWhereHas('vehicle', fn($v) => $v->where('plate', 'like', "%{$placa}%"));
            });
        }

        // (si quisieras filtrar por estado de aprobación, úsalo con $all)
        // if (!$all) { ... }

        $data = $q->paginate($perPage);

        // Empaqueta un resumen de reporte para el front (coincide con lo que espera tu UI)
        $data->getCollection()->transform(function (FuelOrder $o) {
            $rep = $o->latestFuelReport;
            $summary = null;

            if ($rep) {
                $downloadUrl = url("/api/fuel-orders/{$o->id}/report/download");
                $flow = $rep->flow; // puede ser null si aún no hay flujo

                $summary = [
                    'id'               => $rep->id,
                    'status'           => $rep->status,
                    'category'         => $rep->category,
                    'pdf_path'         => $rep->pdf_path,
                    'pdf_page_number'  => (int) $rep->pdf_page_number,
                    'download_url'     => $downloadUrl,
                    'flow'             => $flow ? [
                        'id'           => $flow->id,
                        'current_step' => (int) $flow->current_step,
                        'status'       => $flow->status,
                        'steps'        => $flow->steps->map(fn($s) => [
                            'id'     => $s->id,
                            'order'  => (int) $s->order,
                            'role'   => $s->role,
                            'status' => $s->status,
                            'page'   => (int) $s->page,
                            'pos_x'  => (float)$s->pos_x,
                            'pos_y'  => (float)$s->pos_y,
                            'width'  => (float)$s->width,
                            'height' => (float)$s->height,
                            'callback_token' => $s->callback_token,
                        ]),
                    ] : null,
                ];
            }

            return [
                'id'             => $o->id,
                'fecha'          => $o->fecha,
                'numero'         => $o->numero,
                'vehiculo_placa' => $o->vehiculo_placa,
                'fuel_type'      => $o->fuel_type,
                'quantity_gal'   => $o->quantity_gal,
                'amount_soles'   => $o->amount_soles,
                'supervisor_status' => $o->supervisor_status ?? null,
                'manager_status'    => $o->manager_status ?? null,
                // Para los 3 botones
                'report' => $summary,
            ];
        });

        return response()->json($data);
    }

    /**
     * POST /api/fuel-orders
     * Crea una orden (chofer). driver_id se toma del usuario autenticado.
     * Si envían vehicle_id y NO envían snapshots, se copian desde el vehículo.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required','date'],
            'numero' => ['nullable','string','max:20'],
            'orden_compra' => ['nullable','string','max:50'],
            'componente' => ['nullable','string','max:150'],
            'grifo' => ['nullable','string','max:150'],

            'vehicle_id' => ['nullable','exists:vehicles,id'],
            'vehiculo_marca' => ['nullable','string','max:100'],
            'vehiculo_placa' => ['nullable','string','max:20'],
            'vehiculo_dependencia' => ['nullable','string','max:150'],
            'hoja_viaje' => ['nullable','string','max:50'],
            'motivo' => ['nullable','string'],

            'fuel_type' => ['required', Rule::in(['gasolina','diesel','glp'])],
            'quantity_gal' => ['required','numeric','min:0'],
            'amount_soles' => ['required','numeric','min:0'],
        ]);

        // Rol básico: solo chofer crea. Ajusta si deseas permitir a otros.
        if (($request->user()->role ?? 'chofer') !== 'chofer') {
            return response()->json(['message' => 'Solo chofer puede crear órdenes'], 403);
        }

        // Si tiene vehicle_id y no mandaron snapshots, los tomamos del vehículo
        if (!empty($data['vehicle_id'])) {
            $v = Vehicle::find($data['vehicle_id']);
            if ($v) {
                $data['vehiculo_marca'] = $data['vehiculo_marca'] ?? $v->brand;
                $data['vehiculo_placa'] = $data['vehiculo_placa'] ?? $v->plate;
                $data['vehiculo_dependencia'] = $data['vehiculo_dependencia'] ?? $v->dependencia;
            }
        }

        $data['driver_id'] = $request->user()->id;

        $order = FuelOrder::create($data);

        return response()->json($order->fresh(['driver:id,name','vehicle:id,plate,brand']), 201);
    }

    /**
     * GET /api/fuel-orders/{fuelOrder}
     */
    public function show(Request $request, FuelOrder $fuelOrder)
    {
        $this->authorizeView($request->user(), $fuelOrder);
        return $fuelOrder->load(['driver:id,name', 'vehicle:id,plate,brand']);
    }

    /**
     * PUT/PATCH /api/fuel-orders/{fuelOrder}
     * Solo permite editar mientras ambos estados están pendientes (no decididos).
     * Solo el chofer creador puede editar.
     */
    public function update(Request $request, FuelOrder $fuelOrder)
    {
        $user = $request->user();

        if ($user->id !== $fuelOrder->driver_id) {
            return response()->json(['message' => 'Solo el chofer creador puede editar'], 403);
        }

        if (!is_null($fuelOrder->supervisor_status) || !is_null($fuelOrder->manager_status)) {
            return response()->json(['message' => 'La orden ya fue decidida; no se puede editar'], 409);
        }

        $data = $request->validate([
            'fecha' => ['sometimes','date'],
            'numero' => ['sometimes','nullable','string','max:20'],
            'orden_compra' => ['sometimes','nullable','string','max:50'],
            'componente' => ['sometimes','nullable','string','max:150'],
            'grifo' => ['sometimes','nullable','string','max:150'],

            'vehicle_id' => ['sometimes','nullable','exists:vehicles,id'],
            'vehiculo_marca' => ['sometimes','nullable','string','max:100'],
            'vehiculo_placa' => ['sometimes','nullable','string','max:20'],
            'vehiculo_dependencia' => ['sometimes','nullable','string','max:150'],
            'hoja_viaje' => ['sometimes','nullable','string','max:50'],
            'motivo' => ['sometimes','nullable','string'],

            'fuel_type' => ['sometimes', Rule::in(['gasolina','diesel','glp'])],
            'quantity_gal' => ['sometimes','numeric','min:0'],
            'amount_soles' => ['sometimes','numeric','min:0'],
        ]);

        // Si cambiaron vehicle_id y no mandaron snapshots, refrescamos snapshots
        if (array_key_exists('vehicle_id', $data) && !empty($data['vehicle_id'])) {
            $v = Vehicle::find($data['vehicle_id']);
            if ($v) {
                $data['vehiculo_marca'] = $data['vehiculo_marca'] ?? $v->brand;
                $data['vehiculo_placa'] = $data['vehiculo_placa'] ?? $v->plate;
                $data['vehiculo_dependencia'] = $data['vehiculo_dependencia'] ?? $v->dependencia;
            }
        }

        $fuelOrder->update($data);

        return $fuelOrder->fresh(['driver:id,name','vehicle:id,plate,brand']);
    }

    /**
     * DELETE /api/fuel-orders/{fuelOrder}
     * (Opcional) Solo el chofer y mientras esté pendiente en ambos niveles.
     */
    public function destroy(Request $request, FuelOrder $fuelOrder)
    {
        $user = $request->user();

        if ($user->id !== $fuelOrder->driver_id) {
            return response()->json(['message' => 'Solo el chofer creador puede eliminar'], 403);
        }

        if (!is_null($fuelOrder->supervisor_status) || !is_null($fuelOrder->manager_status)) {
            return response()->json(['message' => 'La orden ya fue decidida; no se puede eliminar'], 409);
        }

        $fuelOrder->delete();
        return response()->noContent();
    }

    /**
     * PATCH /api/fuel-orders/{fuelOrder}/decision
     * Body: { "decision": "approved"|"rejected", "note": "opcional" }
     * - Si es supervisor: marca supervisor_status.
     * - Si es jefe: requiere supervisor_status = 'approved', luego marca manager_status.
     */
    public function decision(Request $request, FuelOrder $fuelOrder)
    {
        $user = $request->user();
        $role = $user->role ?? '';

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved','rejected'])],
            'note' => ['nullable','string'],
        ]);

        // Si ya está rechazada globalmente, bloquear
        if ($fuelOrder->supervisor_status === 'rejected' || $fuelOrder->manager_status === 'rejected') {
            return response()->json(['message' => 'La orden ya fue rechazada previamente'], 409);
        }

        if ($role === 'supervisor') {
            if (!is_null($fuelOrder->supervisor_status)) {
                return response()->json(['message' => 'El supervisor ya decidió esta orden'], 409);
            }

            $fuelOrder->supervisor_status = $data['decision'];
            $fuelOrder->supervisor_id = $user->id;
            $fuelOrder->supervisor_at = now();
            $fuelOrder->supervisor_note = $data['note'] ?? null;
            $fuelOrder->save();

            return $fuelOrder->refresh();
        }

        if ($role === 'jefe') {
            // No permitir que el jefe decida si el supervisor no aprobó
            if ($fuelOrder->supervisor_status !== 'approved') {
                return response()->json(['message' => 'El jefe solo puede decidir tras la aprobación del supervisor'], 422);
            }
            if (!is_null($fuelOrder->manager_status)) {
                return response()->json(['message' => 'El jefe ya decidió esta orden'], 409);
            }

            $fuelOrder->manager_status = $data['decision'];
            $fuelOrder->manager_id = $user->id;
            $fuelOrder->manager_at = now();
            $fuelOrder->manager_note = $data['note'] ?? null;
            $fuelOrder->save();

            return $fuelOrder->refresh();
        }

        return response()->json(['message' => 'Usuario no autorizado para decidir'], 403);
    }

    /**
     * Simple autorización de lectura:
     * - Chofer: solo sus órdenes
     * - Supervisor/Jefe: pueden ver todas (ajusta si quieres restringir)
     */
    protected function authorizeView($user, FuelOrder $fuelOrder): void
    {
        $role = $user->role ?? 'chofer';
        if ($role === 'chofer' && $fuelOrder->driver_id !== $user->id) {
            abort(403, 'No autorizado');
        }
        // supervisor/jefe: permitido ver; personaliza si necesitas
    }

    // POST /api/fuel-orders/{order}/generate-report
    public function generateReport(FuelOrder $order)
    {

        // === 1) Genera PDF sencillo (usa FPDF/tu clase) ===
        $filename     = "fuel_{$order->id}_" . now()->format('Ymd_His') . ".pdf";
        $relativePath = "reports/{$filename}";
        // Generación rápida de PDF (pon aquí tu FpdfExample si prefieres)
        $bytes = $this->makeSimpleFuelPdf($order); // devuelve binario

        Storage::disk('local')->put($relativePath, $bytes);

        // === 2) Cuenta páginas ===
        $pageCount = 1;
        try {
            $absolute = Storage::disk('local')->path($relativePath);
            // $fpdi = new Fpdi();
            $fpdi = new FPDF('P', 'mm', 'A4');

            // $pageCount = (int)$fpdi->setSourceFile($absolute);
            $pageCount = 1;
        } catch (\Throwable $e) { /* log opcional */ }

        // === 3) Crea Report genérico ===
        $report = Report::create([
            'reportable_id'   => $order->id,
            'reportable_type' => FuelOrder::class,
            'pdf_path'        => $relativePath,     // guarda ruta RELATIVA
            'pdf_page_number' => $pageCount,
            'status'          => 'in_progress',
            'category'        => 'fuel_order',
            'created_by'      => Auth::id(),
        ]);

        // === 4) Crea Flow + Steps ===
        $flow = SignatureFlow::create([
            'report_id'    => $report->id,
            'current_step' => 1,
            'status'       => 'in_progress',
        ]);

        // Coordenadas/roles (ajusta a tu plantilla)
        $roles = [
            ['role'=>'fuel_requester','user_id'=>$order->driver_id,    'page'=>1,'pos_x'=>120,'pos_y'=>700,'width'=>180,'height'=>60],
            ['role'=>'fuel_supervisor','user_id'=>null /* set si lo conoces */, 'page'=>1,'pos_x'=>320,'pos_y'=>700,'width'=>180,'height'=>60],
            ['role'=>'fuel_manager',   'user_id'=>null /* set si lo conoces */, 'page'=>1,'pos_x'=>520,'pos_y'=>700,'width'=>180,'height'=>60],
        ];
        foreach (array_values($roles) as $i => $r) {
            SignatureStep::create([
                'signature_flow_id' => $flow->id,
                'order'             => $i+1,
                'role'              => $r['role'],
                'user_id'           => $r['user_id'],
                'page'              => $r['page'],
                'pos_x'             => $r['pos_x'],
                'pos_y'             => $r['pos_y'],
                'width'             => $r['width'],
                'height'            => $r['height'],
                'status'            => 'pending',
                'callback_token'    => Str::random(48),
            ]);
        }

        SignatureEvent::create([
            'signature_flow_id' => $flow->id,
            'event'             => 'flow_created',
            'user_id'           => Auth::id(),
            'meta'              => ['report_id'=>$report->id],
        ]);

        // === 5) Devuelve el PDF para abrir/descargar en el front ===
        // return Storage::disk('local')->download($relativePath, $filename);
        return Storage::download($relativePath, $filename);

    }

     // GET /api/fuel-orders/{order}/report/download
    public function downloadReport(FuelOrder $order)
    {
        $report = $order->latestFuelReport()->firstOrFail();
        $absolute = Storage::disk('local')->path($report->pdf_path);
        $name = basename($report->pdf_path);

        if (!is_file($absolute)) {
            return response()->json(['message' => 'Archivo no disponible.'], 404);
        }
        // return Storage::disk('local')->download($report->pdf_path, $name);
        return Storage::download($report->pdf_path, basename($report->pdf_path));

        // return Storage::download($path, basename($path));
    }

    /**
     * Genera un PDF muy básico con datos del vale.
     * Reemplázalo por tu FpdfExample si quieres estilo.
     */
    private function makeSimpleFuelPdf(FuelOrder $order): string
    {
        $pdf = new \FPDF('L', 'pt', 'A4'); // horizontal para poner 3 firmas cómodas
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0, 24, mb_convert_encoding('VALE DE COMBUSTIBLE', 'ISO-8859-1','UTF-8'), 0, 1, 'C');

        $pdf->SetFont('Arial','',11);
        $y = $pdf->GetY();
        $pdf->SetY($y+10);

        $rows = [
            ['N° Orden', $order->numero ?: '—'],
            ['Fecha',    (string) $order->fecha],
            ['Placa',    $order->vehiculo_placa ?: optional($order->vehicle)->plate],
            ['Combustible', $order->fuel_type],
            ['Galones',  number_format((float)$order->quantity_gal, 2)],
            ['Importe (S/)', number_format((float)$order->amount_soles, 2)],
        ];

        foreach ($rows as [$k,$v]) {
            $pdf->Cell(160, 18, mb_convert_encoding($k, 'ISO-8859-1','UTF-8'), 0, 0);
            $pdf->SetFont('Arial','B',11);
            $pdf->Cell(400, 18, mb_convert_encoding((string)$v, 'ISO-8859-1','UTF-8'), 0, 1);
            $pdf->SetFont('Arial','',11);
        }

        // cajas de firma (sólo referencia visual)
        $pdf->SetY(450);
        foreach (['Solicitante','Supervisor','Jefe'] as $i => $label) {
            $x = 80 + $i*240;
            $pdf->Rect($x, 450, 200, 70);
            $pdf->SetXY($x, 525);
            $pdf->Cell(200, 16, mb_convert_encoding($label, 'ISO-8859-1','UTF-8'), 0, 0, 'C');
        }

        return $pdf->Output('S'); // devuelve binario
    }
     public function showReport(FuelOrder $order)
    {
        $report = $order->latestFuelReport()
            ->with('flow.steps')
            ->first();

        if (!$report) {
            return response()->json(['message' => 'No hay reporte para este vale.'], 404);
        }

        $flow = $report->flow;
        $roles = Auth::user()->getRoleNames()->toArray();

        $currentStep = $flow?->steps?->firstWhere('order', (int)$flow->current_step);
        // return $currentStep
        $userStep = $flow?->steps?->first(function($s) use ($roles) {
            return in_array($s->role, $roles, true);
        });

        $canSign = false;
        if ($userStep && $currentStep) {
            $canSign = $userStep->status === 'pending'
                && (int)$userStep->order === (int)$flow->current_step;
        }

        return response()->json([
            'id'              => $report->id,
            'status'          => $report->status,
            'category'        => $report->category,
            'pdf_path'        => $report->pdf_path,
            'pdf_page_number' => (int)$report->pdf_page_number,
            'download_url'    => url("/api/fuel-orders/{$order->id}/report/download"),

            'flow' => $flow ? [
                'id'           => $flow->id,
                'current_step' => (int)$flow->current_step,
                'status'       => $flow->status,
                'steps'        => $flow->steps->map(fn($s) => [
                    'id'     => $s->id,
                    'order'  => (int)$s->order,
                    'role'   => $s->role,
                    'status' => $s->status,
                    'page'   => (int)$s->page,
                    'pos_x'  => (float)$s->pos_x,
                    'pos_y'  => (float)$s->pos_y,
                    'width'  => (float)$s->width,
                    'height' => (float)$s->height,
                    'callback_token' => $s->callback_token,
                ]),
            ] : null,

            'user_step'    => $userStep ?: null,
            'current_role' => $currentStep?->role,
            'can_sign'     => $canSign,
        ]);
    }
}
