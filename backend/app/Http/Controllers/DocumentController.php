<?php

namespace App\Http\Controllers;

use App\Models\DailyPart;
use App\Models\DocumentDailyPart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class DocumentController extends Controller
{
    public function getPendingDocuments(Request $request){
        Log::info('user id:  ', ['id' => Auth::id()]);
        $documents = DocumentDailyPart::select('documents_daily_parts.*', 'services.description', 'services.goal_detail')
            ->leftJoin('daily_parts', 'documents_daily_parts.id', '=', 'daily_parts.document_id')
            ->leftJoin('services', 'daily_parts.service_id', '=', 'services.id')
            ->where('user_id', Auth::id())
            ->where('documents_daily_parts.state', '!=', 3)
            ->get();
        Log::info('documents:  ', $documents->toArray());
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
            'user_id' => $request->userId
        ]);
        return response()->json([
            'message' => 'Document sent successfully',
            'data' => $document
        ], 201);
    }

    public function getRoles(){
        setPermissionsTeamId(1);
        $user = User::find(Auth::id());
        $role = $user->roles;

        return response()->json([
            'message' => 'roles get successfully',
            'data' => $role
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
}
