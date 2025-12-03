<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    public function getPendingDocuments(){
        $documents = DocumentDailyPart::select(
            'documents_daily_parts.id',
            'documents_daily_parts.user_id',
            'documents_daily_parts.user_id_send',
            'documents_daily_parts.observation',
            'documents_daily_parts.state',
            'documents_daily_parts.file_path',
            'documents_daily_parts.created_at',
            'documents_daily_parts.updated_at',
            'services.description',
            'services.goal_detail',
            DB::raw('COUNT(daily_parts.id) as daily_parts_count'),
            DB::raw('MAX(daily_parts.work_date) as last_work_date')
        )
        ->leftJoin('daily_parts', 'documents_daily_parts.id', '=', 'daily_parts.document_id')
        ->leftJoin('services', 'daily_parts.service_id', '=', 'services.id')
        ->where('user_id', Auth::id())
        ->where('documents_daily_parts.state', '!=', 3)
        ->groupBy(
            'documents_daily_parts.id',
            'documents_daily_parts.user_id',
            'documents_daily_parts.user_id_send',
            'documents_daily_parts.observation',
            'documents_daily_parts.state',
            'documents_daily_parts.file_path',
            'documents_daily_parts.created_at',
            'documents_daily_parts.updated_at',
            'services.description',
            'services.goal_detail'
        )
        ->get();

        return response()->json([
            'message' => 'Documents retrieved successfully',
            'data' => $documents
        ], 201);
    }

    public function sendDocument(Request $request){
        $dailyPart = DailyPart::where('document_id', $request->documentId);
        $dailyPart->update([
            'state' => 4
        ]);

        $document = DocumentDailyPart::find($request->documentId);
        $document->update([
            'user_id' => $request->userId,
            'user_id_send' => Auth::id()
        ]);
        if ($document->state == 1){
            $message = 'Documento correctamente enviado al RESIDENTE';
        } else if ($document->state == 2){
            $message = 'Documento correctamente enviado al SUPERVISOR';
        } else if ($document->state == 3){
            $message = 'proceso de firma de parte diario completado';
        }
        return response()->json([
            'message' => $message,
            'data' => $document
        ], 201);
    }

    function getDocumentSignature($documentId)
    {
        $document = DocumentDailyPart::find($documentId);

        $parser = new Parser();
        $pdf = $parser->parseFile(storage_path('app/public/' . $document->file_path));
        $numPages = count($pdf->getPages());

        return response()->json([
            'message' => 'get document completed successfully',
            'data' => $document,
            'pages' => $numPages
        ], 201);
    }

    public function resendDocument(Request $request){
        $dailyPart = DailyPart::where('document_id', $request->documentId);
        $dailyPart->update([
            'state' => 2
        ]);

        $document = DocumentDailyPart::find($request->documentId);
        $document->update([
            'user_id' => $document->user_id_send,
            'user_id_send' => Auth::id(),
            'observation' => 'Observación: ' . $request->observation
        ]);
        return response()->json([
            'message' => 'Document returned to controller successfully',
            'data' => $document
        ], 201);
    }

    public function deleteDocumentSignature($id){
        $document = DocumentDailyPart::find($id);
        $document->delete();

        return response()->json([
            'message' => 'document deleted successfully'
        ], 204);
    }

    public function prepareMassiveSignature(Request $request)
    {
        try {
            $documentIds = $request->documentIds;
            $documents = DocumentDailyPart::whereIn('id', $documentIds)
                ->where('user_id', Auth::id())
                ->where('state', '!=', 3)
                ->get();

            if ($documents->count() !== count($documentIds)) {
                return response()->json([
                    'message' => 'Algunos documentos no son válidos o no tienes permiso'
                ], 403);
            }

            $tempDir = storage_path('app/public/comp_temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $batchId = uniqid('batch_', true);
            $zipFileName = "{$batchId}.7z";
            $zipFilePath = "{$tempDir}/{$zipFileName}";
            
            $tempBatchDir = "{$tempDir}/batch_{$batchId}";
            if (!is_dir($tempBatchDir)) {
                mkdir($tempBatchDir, 0777, true);
            }

            $rutasArchivos = [];
            $parser = new \Smalot\PdfParser\Parser();
            $documentsInfo = [];

            foreach ($documents as $document) {
                $pdfPath = storage_path('app/public/' . $document->file_path);
                
                if (!file_exists($pdfPath)) {
                    continue;
                }

                $fecha = date('Y-m-d', strtotime($document->last_work_date ?? 'now'));
                $nuevoNombre = "daily_part_{$document->id}_{$fecha}.pdf";
                $nuevaRuta = "{$tempBatchDir}/{$nuevoNombre}";

                if (copy($pdfPath, $nuevaRuta)) {
                    $rutasArchivos[] = escapeshellarg($nuevaRuta);
                } else {
                    continue;
                }

                try {
                    $pdf = $parser->parseFile($pdfPath);
                    $numPages = count($pdf->getPages());
                } catch (\Exception $e) {
                    $numPages = 0;
                }

                $documentsInfo[] = [
                    'id' => $document->id,
                    'pages' => $numPages,
                    'original_name' => basename($document->file_path),
                    'batch_name' => $nuevoNombre
                ];
            }

            if (empty($rutasArchivos)) {
                $this->deleteDirectory($tempBatchDir);
                return response()->json([
                    'message' => 'No se encontraron archivos PDF válidos para comprimir'
                ], 400);
            }

            if (PHP_OS_FAMILY === 'Windows') {
                $comando = '"C:\\Program Files\\7-Zip\\7z.exe" a "' . $zipFilePath . '" ' . implode(' ', $rutasArchivos);
            } else {
                $comando = '7z a "' . $zipFilePath . '" ' . implode(' ', $rutasArchivos);
            }
            
            shell_exec($comando . ' 2>&1');

            if (!file_exists($zipFilePath)) {
                $this->deleteDirectory($tempBatchDir);
                return response()->json([
                    'message' => 'Error al crear el archivo comprimido'
                ], 500);
            }

            $this->deleteDirectory($tempBatchDir);

            return response()->json([
                'message' => 'Batch preparado correctamente',
                'data' => [
                    'batch_id' => $batchId,
                    'zip_file_name' => $zipFileName,
                    'zip_url' => config('app.url') . "/storage/comp_temp/{$zipFileName}",
                    'documents_info' => $documentsInfo,
                    'total_documents' => $documents->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error preparing massive signature: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al preparar firma masiva',
                'error' => $e->getMessage()
            ], 500);
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
    public function sendMassiveDocument(Request $request)
    {
        $documentIds = $request->documentIds;
        $userId = $request->userId;

        $updatedDocuments = [];
        foreach ($documentIds as $documentId) {
            $dailyPart = DailyPart::where('document_id', $documentId)->first();
            if ($dailyPart) {
                $dailyPart->update(['state' => 4]);
            }
            $document = DocumentDailyPart::find($documentId);
            if ($document) {
                $document->update([
                    'user_id' => $userId,
                    'user_id_send' => Auth::id(),
                ]);
                $updatedDocuments[] = $document;
            }
        }
        if (!empty($updatedDocuments)) {
            $firstDocument = $updatedDocuments[0];
            if ($firstDocument->state == 1) {
                $message = 'Documentos correctamente enviados al RESIDENTE';
            } else if ($firstDocument->state == 2) {
                $message = 'Documentos correctamente enviados al SUPERVISOR';
            } else if ($firstDocument->state == 3) {
                $message = 'Proceso de firma de parte diario completado';
            } else {
                $message = 'Documentos enviados correctamente';
            }
        } else {
            $message = 'No se encontraron documentos válidos para actualizar';
        }
        return response()->json([
            'message' => $message,
            'data' => $updatedDocuments,
        ], 201);
    }
}
