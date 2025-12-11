<?php

use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MechanicalEquipmentController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SignatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftsController;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

Route::post('/signature-document/{documentId}/{roleId}', [SignatureController::class, 'storeSignature']);
Route::post('/signature-document/process-massive/{batchId}/{roleId}', [SignatureController::class, 'processMassiveSignatureResponse']);
Route::get('/dailyParts-Pendings', [DailyPartController::class, 'getdailyPartsPendings']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
       $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->pluck('roles.id', 'roles.name');

        $permissions = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->whereIn('role_has_permissions.role_id', $roles->values())
            ->pluck('permissions.name')
            ->unique()
            ->values();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roles->keys()->values(),
            'permissions' => $permissions
        ]);
    });

    // Nueva ruta para obtener permisos del usuario
    Route::get('/user/permissions', function (Request $request) {
        $user = $request->user();
        $permission = $user->getAllPermissions()->pluck('name');
        $roles = $user->getRoleNames();
        return response()->json([
            'permissions' => $permission,
            'roles' => $roles
        ]);
    });

    //Users Routes
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users-incidencia', [UserController::class, 'incidencia']);
    Route::get('/users-consult/{dni}', [UserController::class, 'consultUsers']);
    Route::get('/users-roles', [UserController::class, 'getRoles']);
    Route::post('/users-create', [UserController::class, 'createUser']);
    Route::put('/users-update', [UserController::class, 'updateUser']);
    Route::delete('/users-delete/{id}', [UserController::class, 'destroy']);
    Route::put('/users-update-roles', [UserController::class, 'updateUserRoles']);
    Route::put('/user-change-password', [UserController::class, 'changePassword']);
    Route::get('/users-selected/{documentState}', [UserController::class, 'getUserSelect']);

    Route::middleware(['auth:sanctum', 'role:SuperAdministrador_pd'])->group(function () {
        Route::post('/importUser', [UserController::class, 'importUsersSilucia']);
        Route::post('/importControlador', [UserController::class, 'importControladorSilucia']);
    });

    //Roles Routes
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles-create', [RoleController::class, 'createRole']);
    Route::put('/roles-update', [RoleController::class, 'updateRole']);
    Route::delete('/roles-delete/{id}', [RoleController::class, 'destroy']);
    Route::get('/roles-permissions', [RoleController::class, 'getRolePermissions']);
    Route::put('/roles-permissions', [RoleController::class, 'updateRolePermissions']);

    //orders silucia routes
    Route::post('orders-silucia/import-order', [OrderSiluciaController::class, 'importOrder']);

    //Services
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/selected', [ServiceController::class, 'selectedData']);
    Route::get('/services/daily-parts/{idGoal}', [ServiceController::class, 'getDailyPartsData']);
    Route::get('/services/idmeta/{mechanicalId}', [ServiceController::class, 'getIdmeta']);
    Route::put('/services/idmeta-update/', [ServiceController::class, 'updateIdmeta']);
    Route::delete('/daily-service-delete/{id}', [DailyPartController::class, 'destroyService']);

    //daily work log routes
    Route::get('/daily-work-log/{id}', [DailyPartController::class, 'index']);
    Route::post('/daily-work-log', [DailyPartController::class, 'store']);
    Route::put('/daily-work-log', [DailyPartController::class, 'update']);
    Route::delete('/daily-work-log-delete/{id}', [DailyPartController::class, 'destroy']);
    Route::post('/daily-work-log/complete', [DailyPartController::class, 'completeWork']);
    Route::post('/daily-work-log/{id}/generate-pdf', [DailyPartController::class, 'generatePdf']);
    Route::get('/daily-work-document/{WorkLogId}/{date?}/{shift?}', [DailyPartController::class, 'getDocumentWokLog']);

    //document
    Route::post('/daily-work-document/send', [DocumentController::class, 'sendDocument']);
    Route::get('/document-signature/{documentId}', [DocumentController::class, 'getDocumentSignature']);
    Route::get('/documents-signature/pending', [DocumentController::class, 'getPendingDocuments']);
    Route::post('/document-return/resend-to-controller', [DocumentController::class, 'resendDocument']);
    Route::post('/documents-signature/prepare-massive', [DocumentController::class, 'prepareMassiveSignature']);
    Route::delete('/documents-signature-delete/{id}', [DocumentController::class, 'deleteDocumentSignature']);
    Route::post('document-signature-send/send-massive', [DocumentController::class, 'sendMassiveDocument']);

    //mechanical equipment
    Route::get('/mechanical-equipment', [MechanicalEquipmentController::class, 'index']);
    Route::post('/mechanical-equipment', [MechanicalEquipmentController::class, 'store']);
    Route::put('/mechanical-equipment', [MechanicalEquipmentController::class, 'update']);
    Route::delete('/mechanical-equipment/{id}', [MechanicalEquipmentController::class, 'destroy']);
    Route::post('/mechanical-equipment/support-machinery', [MechanicalEquipmentController::class, 'supportMachinery']);

    //products
    Route::get('/products-select', [ProductController::class, 'consultaProductSelect']);

    //shifts
    Route::get('/shifts-select', [ShiftsController::class, 'consultaShifts']);

    //evendence
    Route::get('/daily-work-evendece/{serviceId}', [EvidenceController::class, 'getEvidence']);

    //Operators
    Route::get('/operators-select/{serviceId}', [OperatorController::class, 'getOperators']);

    //reports
    Route::get('/report-id/liquidation/{id}', [ReportController::class, 'getLiquidationData']);
    Route::post('/reports/report-generate-request', [ReportController::class, 'generateRequest']);
    Route::post('/reports/report-generate-auth', [ReportController::class, 'generateAuth']);
    Route::post('/reports/report-generate-liquidation', [ReportController::class, 'generateLiquidation']);
    Route::post('/reports/report-generate-valorization', [ReportController::class, 'generateValorization']);
    Route::post('/reports/save-auth-changes', [ReportController::class, 'saveAuthChanges']);
    Route::post('/reports/download-merged-daily-parts/{serviceId}', [ReportController::class, 'downloadMergedDailyParts']);
    Route::post('/reports/close-service/{serviceId}', [ReportController::class, 'closeService']);
    Route::get('/report-id/adjusted-liquidation/{serviceId}', [ReportController::class, 'getAdjustedLiquidationData']);

    //signature
    Route::post('/signature-password', [SignatureController::class, 'signatureOfPassword']);
    Route::post('/signature-password-massive', [SignatureController::class, 'signatureOfPasswordMassive']);
});
