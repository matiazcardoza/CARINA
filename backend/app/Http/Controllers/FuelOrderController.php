<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\FuelOrder;
use App\Models\Vehicle;
use Illuminate\Validation\Rule;

class FuelOrderController extends Controller
{
    /**
     * GET /api/fuel-orders
     * - Chofer: ve SOLO sus órdenes (propias).
     * - Supervisor: ve pendientes de supervisor (supervisor_status = null).
     * - Jefe: ve pendientes de jefe (supervisor_status = 'approved' y manager_status = null).
     * Puedes pasar ?all=1 para ver todo (solo para supervisor/jefe).
     */
    public function index(Request $request)
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
}
