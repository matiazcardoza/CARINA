<?php
// app/Http/Controllers/SignaturesController.php

namespace App\Http\Controllers;

use App\Models\SignatureFlow;
use App\Models\SignatureStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignaturesController extends Controller
{
    // POST /api/signatures/callback?flow_id=&step_id=&token=
    public function callback(Request $req)
    {
        $flowId = (int) $req->query('flow_id');
        $stepId = (int) $req->query('step_id');
        $token  = (string) $req->query('token');

        $flow = SignatureFlow::with(['report','steps'])->findOrFail($flowId);
        $step = SignatureStep::where('id', $stepId)
            ->where('signature_flow_id', $flow->id)
            ->firstOrFail();

        // Validaciones mínimas
        if ($step->callback_token !== $token) {
            return response()->json(['message' => 'Token inválido.'], 403);
        }
        if ((int)$step->order !== (int)$flow->current_step) {
            return response()->json(['message' => 'Este paso no está activo.'], 409);
        }
        if ($step->status !== 'pending') {
            return response()->json(['message' => 'Paso ya resuelto.'], 409);
        }

        // Autorización simple por roles (ajústalo a tu lógica)
        $user = Auth::user();
        $userRoles = $user->getRoleNames()->toArray();
        if (!in_array($step->role, $userRoles, true)) {
            return response()->json(['message' => 'No puedes firmar este paso.'], 403);
        }

        // Marca paso como firmado
        $step->forceFill([
            'status'    => 'signed',
            'signed_at' => now(),
            'user_id'   => $user->id,
        ])->save();

        // Avanza el flujo o ciérralo
        $totalSteps = $flow->steps->count();
        if ((int)$flow->current_step >= $totalSteps) {
            $flow->forceFill(['status' => 'completed'])->save();
            $flow->report->forceFill(['status' => 'completed'])->save();
        } else {
            $flow->forceFill(['current_step' => $flow->current_step + 1])->save();
        }

        // (opcional) registra evento

        return response()->json(['ok' => true, 'flow_status' => $flow->status]);
    }
}
