<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class SignatureController extends Controller
{
    public function storeSignature(Request $request, $DocumentId, $roleId)
    {
        Log::info("Ingreso a storeSignature con DocumentId: {$DocumentId}, roleId: {$roleId}, userId: " . Auth::id());
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

        if($roleId == 3){
            $document->update(['state' => 1]);
        } elseif ($roleId == 4){
            $document->update(['state' => 2]);
        } elseif ($roleId == 5){
            $document->update(['state' => 3]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Archivo firmado guardado y actualizado exitosamente.'
        ]);
    }

    public function processMassiveSignatureResponse(Request $request, $batchId, $roleId)
    {
        try {
            if (!$request->hasFile('signed_file')) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se recibió el archivo firmado'
                ], 400);
            }
            $signedFile = $request->file('signed_file');

            $tempDir = storage_path('app/public/comp_temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempSignedPath = $tempDir . '/signed_' . $batchId . '.7z';
            $signedFile->move($tempDir, 'signed_' . $batchId . '.7z');
            if (!file_exists($tempSignedPath)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se pudo guardar el archivo firmado'
                ], 500);
            }
            $extractDir = $tempDir . '/extracted_' . $batchId;
            if (!is_dir($extractDir)) {
                mkdir($extractDir, 0777, true);
            }
            if (PHP_OS_FAMILY === 'Windows') {
                $extractCommand = '"C:\\Program Files\\7-Zip\\7z.exe" x "' . $tempSignedPath . '" -o"' . $extractDir . '" -y';
            } else {
                $extractCommand = '7z x "' . $tempSignedPath . '" -o"' . $extractDir . '" -y';
            }
            shell_exec($extractCommand . ' 2>&1');
            if (!is_dir($extractDir)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Error al extraer archivos'
                ], 500);
            }
            $extractedFiles = scandir($extractDir);
            $extractedFiles = array_diff($extractedFiles, ['.', '..']);

            if (count($extractedFiles) === 0) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se encontraron archivos en el archivo comprimido'
                ], 500);
            }
            $processedCount = 0;
            $errorCount = 0;
            $processedDocs = [];
            foreach ($extractedFiles as $file) {
                $fullPath = $extractDir . '/' . $file;
                if (!is_file($fullPath)) {
                    continue;
                }
                preg_match('/daily_part_(\d+)_/', $file, $matches);
                if (!isset($matches[1])) {
                    $errorCount++;
                    continue;
                }
                $documentId = (int)$matches[1];
                $document = DocumentDailyPart::find($documentId);
                if (!$document) {
                    $errorCount++;
                    continue;
                }
                try {
                    $destinationPath = storage_path('app/public/' . $document->file_path);
                    $dir = dirname($destinationPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    if (copy($fullPath, $destinationPath)) {
                        $oldState = $document->state;
                        $newState = $this->calculateNewState($document->state, $roleId);
                        $document->update(['state' => $newState]);
                        if ($document->work_log_id) {
                            $dailyPart = DailyPart::find($document->work_log_id);
                            if ($dailyPart) {
                                $dailyPartState = $this->calculateDailyPartState($newState);
                                $dailyPart->update(['state' => $dailyPartState]);
                            }
                        }
                        $processedCount++;
                        $processedDocs[] = [
                            'id' => $documentId,
                            'file' => $file,
                            'old_state' => $oldState,
                            'new_state' => $newState
                        ];
                    } else {
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Error procesando documento ' . $documentId . ': ' . $e->getMessage());
                    $errorCount++;
                }
            }
            $this->cleanupTempFiles($batchId, $tempSignedPath, $extractDir);
            return response()->json([
                'ok' => true,
                'message' => 'Firma masiva procesada correctamente',
                'processed' => $processedCount,
                'errors' => $errorCount,
                'documents' => $processedDocs
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error crítico en procesamiento masivo: ' . $e->getMessage());

            return response()->json([
                'ok' => false,
                'error' => 'Error al procesar firma masiva: ' . $e->getMessage()
            ], 500);
        }
    }

    private function cleanupTempFiles($batchId, $tempSignedPath, $extractDir)
    {
        try {
            if (file_exists($tempSignedPath)) {
                unlink($tempSignedPath);
            }
            if (is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }
            $originalZipPath = storage_path('app/public/comp_temp/' . $batchId . '.7z');
            if (file_exists($originalZipPath)) {
                unlink($originalZipPath);
            }
        } catch (\Exception $e) {
            Log::warning('Error al limpiar archivos temporales: ' . $e->getMessage());
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function calculateNewState($currentState, $roleId)
    {
        switch ($roleId) {
            case 3:
                return 1;
            case 4:
                return 2;
            case 5:
                return 3;
            default:
                return $currentState;
        }
    }

    private function calculateDailyPartState($documentState)
    {
        return $documentState === 3 ? 5 : 4;
    }

    public function signatureOfPassword(Request $request)
    {
        $documentId = $request->input('documentId');

        $user = DB::table('users')
            ->join('personas', 'users.id', '=', 'personas.user_id')
            ->select('users.*', 'personas.name as persona_name', 'personas.last_name', 'personas.num_doc')
            ->where('users.id', Auth::id())
            ->first();

        Log::info('User: ' . json_encode($user));
        if (!$user || empty($user->num_doc)) {
            return response()->json(['correcto' => false, 'mensaje' => 'Usuario inválido'], 401);
        }

        $payload = [
            'username' => $user->num_doc,
            'password' => $user->password,
        ];

        /*$url = 'https://sistemas.regionpuno.gob.pe/siluciav2-api/api/silucia-auth';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.silucia.api_token'),
                    'Content-Type' => 'application/json',
                ])
                ->withBody(json_encode($payload), 'application/json')
                ->post($url);

            if (!$response->successful()) {
                return response()->json(['correcto' => false, 'mensaje' => 'Contraseña incorrecta'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['correcto' => false, 'mensaje' => 'Error al conectar con API externa'], 500);
        }*/

        $document = DocumentDailyPart::find($documentId);
        if (!$document) {
            return response()->json(['correcto' => false, 'mensaje' => 'Documento no encontrado'], 404);
        }

        $filePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['correcto' => false, 'mensaje' => 'Archivo PDF no encontrado'], 404);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        copy($filePath, $tmpPath);

        $drawnPdfPath = tempnam(sys_get_temp_dir(), 'pdf_signed_') . '.pdf';

        $textoFirma =
            "Firmado con contraseña por:\n" .
            $user->persona_name . ' ' . $user->last_name . "\n" .
            "(" . ($user->num_doc ?? '') . ")\n" .
            "Cargo: CONTROLADOR\n" .
            "Fecha: " . now()->format('d/m/Y H:i:s');

        $posX = 80;
        $posY = 695;

        $this->drawOnPdf($tmpPath, $drawnPdfPath, $textoFirma, $posX, $posY);

        $destinationPath = storage_path('app/public/' . $document->file_path);
        copy($drawnPdfPath, $destinationPath);
        $document->state = 1;
        $document->save();

        if (file_exists($tmpPath)) unlink($tmpPath);
        if (file_exists($drawnPdfPath)) unlink($drawnPdfPath);

        return response()->json([
            'correcto' => true,
            'mensaje' => 'Documento firmado exitosamente',
            'document' => $document
        ]);
    }

    private function drawOnPdf($inputPdf, $outputPdf, $text, $x, $y)
    {
        $pdf = new Fpdi('P', 'pt');
        $pageCount = $pdf->setSourceFile($inputPdf);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            // Solo en la última página
            if ($i === $pageCount) {
                // Insertar logo (opcional)
                $imgPath = public_path('storage/image_pdf_template/logo_grp.png');
                $imgWidth = 22;

                if (file_exists($imgPath)) {
                    $pdf->Image($imgPath, $x, $y, $imgWidth);
                }

                // Insertar texto
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(0, 0, 0);

                $textX = $x + $imgWidth + 3;
                $textY = $y;
                $pdf->SetXY($textX, $textY);
                $textDecoded = utf8_decode($text);
                $pdf->MultiCell(150, 9, $textDecoded, 0, 'L');
            }
        }

        $pdf->Output($outputPdf, "F");
    }

    public function signatureOfPasswordMassive(Request $request)
    {
        $documentIds = $request->input('documentIds');

        // Validar que lleguen IDs
        if (!$documentIds || !is_array($documentIds) || count($documentIds) === 0) {
            return response()->json([
                'correcto' => false,
                'mensaje' => 'No se recibieron documentos para firmar'
            ], 400);
        }

        // Obtener información del usuario
        $user = DB::table('users')
            ->join('personas', 'users.id', '=', 'personas.user_id')
            ->select('users.*', 'personas.name as persona_name', 'personas.last_name', 'personas.num_doc')
            ->where('users.id', Auth::id())
            ->first();

        Log::info('User signing massive: ' . json_encode($user));

        if (!$user || empty($user->num_doc)) {
            return response()->json([
                'correcto' => false,
                'mensaje' => 'Usuario inválido'
            ], 401);
        }

        // Validar contraseña con API externa (comentado por ahora)
        /*$payload = [
            'username' => $user->num_doc,
            'password' => $user->password,
        ];

        $url = 'https://sistemas.regionpuno.gob.pe/siluciav2-api/api/silucia-auth';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.silucia.api_token'),
                    'Content-Type' => 'application/json',
                ])
                ->withBody(json_encode($payload), 'application/json')
                ->post($url);

            if (!$response->successful()) {
                return response()->json([
                    'correcto' => false,
                    'mensaje' => 'Contraseña incorrecta'
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('Error validando contraseña: ' . $e->getMessage());
            return response()->json([
                'correcto' => false,
                'mensaje' => 'Error al conectar con API externa'
            ], 500);
        }*/

        $textoFirma =
            "Firmado con contraseña por:\n" .
            $user->persona_name . ' ' . $user->last_name . "\n" .
            "(" . ($user->num_doc ?? '') . ")\n" .
            "Cargo: CONTROLADOR\n" .
            "Fecha: " . now()->format('d/m/Y H:i:s');

        $posX = 80;
        $posY = 695;

        $signedCount = 0;
        $errorCount = 0;
        $errors = [];
        $signedDocuments = [];

        // Procesar cada documento
        foreach ($documentIds as $documentId) {
            try {
                $document = DocumentDailyPart::find($documentId);

                if (!$document) {
                    $errorCount++;
                    $errors[] = [
                        'documentId' => $documentId,
                        'error' => 'Documento no encontrado'
                    ];
                    continue;
                }

                $filePath = storage_path('app/public/' . $document->file_path);

                if (!file_exists($filePath)) {
                    $errorCount++;
                    $errors[] = [
                        'documentId' => $documentId,
                        'error' => 'Archivo PDF no encontrado'
                    ];
                    continue;
                }

                // Crear copias temporales
                $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
                copy($filePath, $tmpPath);

                $drawnPdfPath = tempnam(sys_get_temp_dir(), 'pdf_signed_') . '.pdf';

                // Dibujar firma en el PDF
                $this->drawOnPdf($tmpPath, $drawnPdfPath, $textoFirma, $posX, $posY);

                $destinationPath = storage_path('app/public/' . $document->file_path);
                copy($drawnPdfPath, $destinationPath);
                $document->state = 1; // Cambiar a estado "Firmado por Controlador"
                $document->save();

                // Limpiar archivos temporales
                if (file_exists($tmpPath)) unlink($tmpPath);
                if (file_exists($drawnPdfPath)) unlink($drawnPdfPath);

                $signedCount++;
                $signedDocuments[] = [
                    'id' => $document->id,
                    'description' => $document->description,
                    'new_state' => $document->state
                ];

                Log::info("Documento {$documentId} firmado exitosamente");

            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = [
                    'documentId' => $documentId,
                    'error' => $e->getMessage()
                ];
                Log::error("Error firmando documento {$documentId}: " . $e->getMessage());
            }
        }

        // Preparar respuesta
        $responseData = [
            'correcto' => $signedCount > 0,
            'mensaje' => $signedCount > 0
                ? "{$signedCount} documento(s) firmado(s) exitosamente"
                : "No se pudo firmar ningún documento",
            'total_solicitados' => count($documentIds),
            'firmados_exitosamente' => $signedCount,
            'errores' => $errorCount,
            'documentos_firmados' => $signedDocuments
        ];

        if ($errorCount > 0) {
            $responseData['detalles_errores'] = $errors;
        }

        $statusCode = $signedCount > 0 ? 200 : 400;

        return response()->json($responseData, $statusCode);
    }
}


