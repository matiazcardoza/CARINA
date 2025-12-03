<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    private function drawOnPdf($inputPdf, $outputPdf, $text, $x, $y)
    {
        $pdf = new Fpdi('P', 'pt');
        $pageCount = $pdf->setSourceFile($inputPdf);

        for ($i = 1; $i <= $pageCount; $i++) {

            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            if ($i === $pageCount) {

                // -----------------------------
                // 1️⃣ Insertar imagen (firma) a la izquierda
                // -----------------------------
                //$imgPath = public_path(aqui tiene que ser un logo que esta en la carpeta public/storage/image_pdf_template/logo-gore.png

                $imgWidth = 22; // tamaño img
                //$imgHeight = 0; // altura automática

                //if (file_exists($imgPath)) {
                 //   $pdf->Image($imgPath, $x, $y, $imgWidth, $imgHeight);
               // }

                // -----------------------------
                // 2️⃣ Insertar texto a la derecha de la imagen
                // -----------------------------
                $pdf->SetFont('Helvetica', '', 6);
                $pdf->SetTextColor(0, 0, 0);

                // POSICIÓN DEL TEXTO (derecha de la imagen con margen 2pt)
                $textX = $x + $imgWidth + 2;
                $textY = $y;
                $pdf->SetXY($textX, $textY);

                // ESCALA REAL
                //$pdf->StartTransform();
                //$pdf->Scale(70, 70); // 70% del tamaño original → pequeño
                $pdf->MultiCell(120, 8, $text, 0, 'L');
                //$pdf->StopTransform();
            }
        }

        $pdf->Output($outputPdf, "F");
    }

    public function signatureOfPassword(Request $request)
    {
        $request->validate([
            'row' => 'required|array',
            'password' => 'required|string',
        ]);

        $user = User::find(Auth::id());

        if (!$user || empty($user->persona->num_doc)) {
            return response()->json(['correcto' => false, 'mensaje' => 'Usuario inválido'], 401);
        }

        $payload = [
            'username' => $user->persona->num_doc,
            'password' => $request->password,
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
                return response()->json(['correcto' => false, 'mensaje' => 'Contraseña incorrecta'], 401);
            }

        } catch (\Exception $e) {
            return response()->json(['correcto' => false, 'mensaje' => 'Error al conectar con API externa'], 500);
        }

        $row = $request->input('row');
        $realFilename = $row['real_filename'] ?? null;
        if (!$realFilename) {
            return response()->json(['correcto' => false, 'mensaje' => 'Archivo no disponible en el row'], 422);
        }

        $filePath = storage_path('public/storage/daily_parts/' . $realFilename . '.pdf');

        if (!file_exists($filePath)) {
            return response()->json(['correcto' => false, 'mensaje' => 'Archivo no encontrado en storage'], 404);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        copy($filePath, $tmpPath);


        $drawnPdfPath = tempnam(sys_get_temp_dir(), 'pdf_signed_') . '.pdf';


        $textoFirma = 
            "Firmado con contraseña por:\n" .
            $user->name . " " . ($user->persona->num_doc ?? '') . "\n" .
            "Motivo: Firma con contraseña\n" .
            "Fecha: " . now()->format('d/m/Y H:i:sO');


        $this->drawOnPdf(
            $tmpPath,
            $drawnPdfPath,
            $textoFirma,
            $row['current_step']['pos_x'], 
            $row['current_step']['pos_y'], 
            9
        );

        $tmpFile = new \Illuminate\Http\UploadedFile(
            $drawnPdfPath,
            $row['display_name'] ?? $realFilename,
            'application/pdf',
            null,
            true
        );

        $storev2Request = \Illuminate\Http\Request::create(
            '/fake/storev2?userId=' . ($row['auth_user_id'] ?? $user->id)
                . '&obraId=' . $row['reportable_id']
                . '&reportId=' . $row['id']
                . '&token=' . ($row['current_step']['callback_token'] ?? ''),
            'POST',
            [],
            [],
            ['file' => $tmpFile],
            []
        );
        //$response = app()->make(\App\Http\Controllers\SignatureController::class)
           // ->storev2($storev2Request);

        if (file_exists($tmpPath)) {
            unlink($tmpPath);
        }

        return $response;
    }
}


