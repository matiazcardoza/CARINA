<?php

namespace App\Http\Controllers;

use App\Models\DocumentDailyPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SignatureController extends Controller
{
    public function storeSignature(Request $request, $DocumentId, $roleId)
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
}


