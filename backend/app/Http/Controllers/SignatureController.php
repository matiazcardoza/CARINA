<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
}


