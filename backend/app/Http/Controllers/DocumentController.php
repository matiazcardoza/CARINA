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
            'user_id' => $request->userId
        ]);
        return response()->json([
            'message' => 'Document sent successfully',
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
            'state' => 3
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
}
