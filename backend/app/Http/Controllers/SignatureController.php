<?php

namespace App\Http\Controllers;

use App\Models\DocumentDailyPart;
use App\Models\Report;
use App\Models\SignatureFlow;
use App\Models\SignatureStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;      
use Illuminate\Support\Facades\Log;
use App\Models\SignatureEvent;
use App\Utils\UsefulFunctionsForPdfs;

class SignatureController extends Controller
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
    public function store(Request $req)
    {
        // 1) Identificación segura del paso
        // $flowId = (int) $req->query('flow_id');
        $reportId = (int) $req->query('report_id');
        $stepId = (int) $req->query('step_id');
        $token  = (string) $req->query('token');
        $userId    = (int) $req->query('user_id');
        // $userRoles = (string) $req->query('user_roles');
        $userRoles  = $req->query('user_roles', []);


        // normaliza a array si llega como string o CSV
        if (is_string($userRoles)) {
            $userRoles = array_filter(array_map('trim', explode(',', $userRoles)));
        }
        $userRoles = array_map('trim', (array) $userRoles);

        // Acepta distintos nombres de campo y mimes (muchos proveedores mandan octet-stream)
        $req->validate([
            'file'        => 'nullable|mimetypes:application/pdf,application/octet-stream|max:30720',
            'signed_file' => 'nullable|mimetypes:application/pdf,application/octet-stream|max:30720',
            'pdf'         => 'nullable|mimetypes:application/pdf,application/octet-stream|max:30720',
        ]);
        // $uploaded es el archivo pdf, qui se guarda el primer archivo devuelto por el firmador
        $uploaded = $req->file('file') ?? $req->file('signed_file') ?? $req->file('pdf');
        /**
         * abort_unless sirve para abortar la peticion si el valor del primer parametro es falso. ejemplo:
         * abort_unless($condición, $codigoHttp, $mensajeOpcional, $headersOpcionales);
         */

        abort_unless($uploaded, 422, 'Archivo PDF requerido.');

        // 2) Carga entidades
        // $flow = SignatureFlow::with(['report','steps'])->findOrFail($flowId);
        $report = Report::with('steps')->findOrFail($reportId);
        $step = SignatureStep::findOrFail($stepId);

        // Si el paso está asignado a un usuario específico, exige ese user_id
        if ($step->user_id) {
            abort_unless((int)$userId === (int)$step->user_id, 403, 'Este paso está asignado a otro usuario.');
        }

        // Si el paso exige un rol, compara contra rol puntual y/o lista de roles del usuario
        // Si el paso está asignado a un usuario específico, exige ese user_id
        if ($step->user_id) {
            abort_unless($userId > 0 && (int)$userId === (int)$step->user_id, 403, 'Este paso está asignado a otro usuario.');
        }

        // Si el paso exige un rol, valida contra los roles del usuario (user_roles)
        if (!empty($step->role)) {
            abort_unless(in_array($step->role, $userRoles, true), 403, 'Lo siento, usted no tiene permiso para firmar este PDF.');
        }

        // 3) Autorización del paso/turno
        // verificamos si stespSignature pertenece a signatureFlow
        abort_unless($step->report_id === $report->id, 404);
        // verificamos si el flujo "flow" esta en progreso
        abort_if($report->status !== 'in_progress', 409, 'Flujo no activo.');
        /**
         * si el paso actual (step) es diferente de pendign abortamos el firmado,
         * verificamos si en el flujo el step actual puede firmar es el esperado para firmar
         */
        abort_if($step->status !== 'pending' || (int)$step->order !== (int)$report->current_step, 409, 'Paso no activo.');
        /**
         * verificamos si el step actual es quien ha enviado el token para firmar (es una seguridad ), aqui podemos adicionar un control de seguridad más
         * es decir en el paso actual puedo obtener el hash del pdf que espero y lo comparo con el hash del pdf que ha llegado, si ambos son iguales se procede a 
         * firmar si son diferentes no se firma. posteriormente cuando la firma se realice, para el siguiente paso le datos e hash del pdf ya firmado, actualmente
         * en la columna sha256 se guarda el hash del pdf firmado
         */
        abort_unless(hash_equals((string)$step->callback_token, $token), 403, 'Token inválido.');

        // 4) Normaliza ruta: SIEMPRE en app/private/silucia_product_reports/<filename>
        $directory      = 'silucia_product_reports';
        $stored   = (string) $report->pdf_path;                 // puede ser "kardex_...pdf" o incluir carpeta
        // $filename = basename($stored);                          // asegura solo el nombre del archivo
        $filename = UsefulFunctionsForPdfs::extractPdfFilename($report->pdf_path);                          // asegura solo el nombre del archivo
        $current  = $directory . '/' . $filename;                     // ruta RELATIVA dentro del disk 'local'

        // Crea la carpeta si no existe
        Storage::disk('local')->makeDirectory($directory);

        // TMP en la MISMA carpeta para poder mover dentro del mismo filesystem
        $tmp = $directory . '/.__incoming_' . uniqid() . '.pdf';

        // Sube a TMP y calcula hash. guardamos el archivo obeenido del front en el directorio tmp (temporal)
        Storage::disk('local')->put($tmp, file_get_contents($uploaded->getRealPath()));
        $bytes = Storage::disk('local')->get($tmp);
        $hash  = hash('sha256', $bytes);

        return DB::transaction(function() use ($current, $tmp, $hash, $report, $step, $req, $userId) {
            // Swap atómico: reemplaza el PDF original por el firmado
            Storage::disk('local')->delete($current);
            Storage::disk('local')->move($tmp, $current);

            // Marcar paso firmado + metadata
            $step->update([
                'status'             => 'signed',
                'signed_at'          => now(),
                // para indicar la persona que firmo, se debe enviar desde el front el id de la persona que firma el pdf
                'signed_by'         => $userId, 
                'provider'           => 'firma_peru',
                'sha256'             => $hash,
                // recomendado: invalidar token si lo usas de un solo uso
                // 'callback_token'  => null,
            ]);

            // Avanzar o completar flujo
            /**
             * actualizamos la columna current step verificanso si todavia hay firmantes, si no hay firmantes
             * cerramos todo, es decir a todo le damos el valor completado
             */
            $next = $report->steps()->where('order','>', $step->order)->orderBy('order')->first();
            if ($next) {
                $report->update(['current_step' => $next->order]);
            } else {
                $report->update(['status'=>'completed']);
                // $report->report->update(['status'=>'completed']);
            }

            // (Opcional) Log con ruta absoluta para ver dónde quedó
            Log::info('PDF firmado guardado', [
                'abs' => Storage::disk('local')->path($current), // storage/app/private/silucia_product_reports/...
            ]);

            return response()->json(['ok'=>true]);
        });
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



    public function storev0(Request $req)
    {
        
        // Log::info('Callback recibido', [
        //     'flow_id' => $req->query('flow_id'),
        //     'step_id' => $req->query('step_id'),
        //     'token'   => $req->query('token'),
        //     'provider_tx_id' => $req->input('provider_tx_id'),
        //     'certificate_cn' => $req->input('certificate_cn'),
        //     'certificate_serial' => $req->input('certificate_serial'),
        //     'file_present' => $req->hasFile('file'),
        // ]);
        // 1) Identificación segura del paso
        $flowId = (int) $req->query('flow_id');
        $stepId = (int) $req->query('step_id');
        $token  = (string) $req->query('token');

        $req->validate([
            'file' => 'required|file|mimes:pdf|max:30720', // 30MB
        ]);

        // 2) Carga entidades
        $flow = SignatureFlow::with(['report','steps'])->findOrFail($flowId);
        $step = SignatureStep::findOrFail($stepId);

        // 3) Autorización del paso/turno
        abort_unless($step->signature_flow_id === $flow->id, 404);
        abort_if($flow->status !== 'in_progress', 409, 'Flujo no activo.');
        abort_if($step->status !== 'pending' || $step->order !== $flow->current_step, 409, 'Paso no activo.');
        abort_unless(hash_equals((string)$step->callback_token, $token), 403, 'Token inválido.');

        // 4) Guardado atómico: reemplazar el MISMO archivo (single file)
        $current = $flow->report->pdf_path; // ej: silucia_product_reports/kardex_...pdf
        
        $tmp     = dirname($current).'/.__incoming_'.uniqid().'.pdf';

        // resultado de tmp: $tmp = 'silucia_product_reports/.__incoming_64f0c1e2e7a3a7.pdf';

        // Log::info("tmp:  ", $tmp);
        // Log::info('00002', ['role' => $tmp]);
        Storage::disk('local')->put($tmp, file_get_contents($req->file('file')->getRealPath()));
        $bytes = Storage::disk('local')->get($tmp);
        $hash  = hash('sha256', $bytes);

        return DB::transaction(function() use ($current, $tmp, $hash, $flow, $step, $req) {
            Storage::disk('local')->delete($current);
            Storage::disk('local')->move($tmp, $current);

            // marcar firmado
            $step->update([
                'status'            => 'signed',
                'signed_at'         => now(),
                'provider'          => 'firma_peru',
                'provider_tx_id'    => $req->input('provider_tx_id'),
                'certificate_cn'    => $req->input('certificate_cn'),
                'certificate_serial'=> $req->input('certificate_serial'),
                'sha256'            => $hash,
            ]);

            // avanzar o completar
            $next = $flow->steps()->where('order','>', $step->order)->orderBy('order')->first();
            if ($next) {
                $flow->update(['current_step' => $next->order]);
            } else {
                $flow->update(['status'=>'completed']);
                $flow->report->update(['status'=>'completed']);
            }

            SignatureEvent::create([
                'signature_flow_id' => $flow->id,
                'signature_step_id' => $step->id,
                'event'             => 'callback_received',
                'meta'              => ['sha256'=>$hash],
            ]);

            return response()->json(['ok'=>true]);
        });     
        // Log::info('00004', ['current' => $response]);
        // Log::info($response);
        // return $response;
    }
    // POST /api/signatures/callback?flow_id=...&step_id=...&token=...
    public function firmaPeruCallback(Request $req)
    {
        $flow = SignatureFlow::findOrFail($req->query('flow_id'));
        $step = SignatureStep::findOrFail($req->query('step_id'));
        $token= $req->query('token');

        abort_unless($step->callback_token && hash_equals($step->callback_token, $token), 403);
        abort_if($flow->status !== 'in_progress', 409);
        abort_if($step->signature_flow_id !== $flow->id, 404);
        abort_if($step->status !== 'pending' || $step->order !== $flow->current_step, 409, 'Paso no activo');

        $req->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
            // (opcional) metadatos: provider_tx_id, certificate_cn, certificate_serial
        ]);

        $dir = "signflows/{$flow->id}";
        Storage::disk('local')->makeDirectory($dir);
        $target = "{$dir}/signed_step_{$step->order}_" . now()->format('Ymd_His') . ".pdf";
        Storage::disk('local')->put($target, file_get_contents($req->file('file')->getRealPath()));

        $step->update([
            'status'            => 'signed',
            'signed_at'         => now(),
            'provider'          => 'firma_peru',
            'provider_tx_id'    => $req->input('provider_tx_id'),
            'certificate_cn'    => $req->input('certificate_cn'),
            'certificate_serial'=> $req->input('certificate_serial'),
            'signed_pdf_path'   => $target,
        ]);

        $flow->report->update(['latest_pdf_path' => $target]);

        $next = $flow->steps()->where('order', '>', $step->order)->orderBy('order')->first();
        if ($next) {
            $flow->update(['current_step' => $next->order]);
        } else {
            $flow->update(['status'=>'completed']);
            $flow->report->update(['status'=>'completed']);
        }

        SignatureEvent::create([
            'signature_flow_id'=>$flow->id,
            'signature_step_id'=>$step->id,
            'event'=>'callback_received',
            'meta'=>['path'=>$target]
        ]);

        return response()->json(['ok'=>true]);
    }

    // public function exportPdf($path){
    public function filesDownload(Request $request){
        $name = $request->query('name');
        $path = 'silucia_product_reports/' . basename($name);
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }
        return Storage::download($path, basename($path));
    }


    /////////////////////////////////////////CONTROLADOR PARA PARTES DIARIAS//////////////////////////////////////////////////////////////////////
    
    public function storeSignature(Request $request, $DocumentId)
    {
        $document = DocumentDailyPart::find($DocumentId);
        if ($request->hasFile('signed_file')) {
            $signedFile = $request->file('signed_file');
            $filePath = $document->file_path;
            try {
                Storage::disk('public')->put($filePath, file_get_contents($signedFile->getRealPath()));
            } catch (\Exception $e) {
                Log::error('Error al guardar el archivo firmado: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to save the signed file'], 500);
            }
        } else {
            return response()->json(['error' => 'No file was uploaded'], 400);
        }
        
        if($document->state == 0){
            $document->update(['state' => 1]);
        } elseif ($document->state == 1){
            $document->update(['state' => 2]);
        } elseif ($document->state == 2){
            $document->update(['state' => 3]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Archivo firmado guardado y actualizado exitosamente.'
        ]);
    }
}


