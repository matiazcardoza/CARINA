<?php

use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MechanicalEquipmentController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SignatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShiftsController;
use Illuminate\Support\Facades\DB;

Route::post('/document-signature/{documentId}', [SignatureController::class, 'storeSignature']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        // Obtener todos los roles del usuario sin filtrar por team_id
       $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.model_type', get_class($user))
            ->pluck('roles.id', 'roles.name');

        // Obtener los permisos asociados a esos roles
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
            // 'roles' => $user->getRoleNames(),
            'roles' => $roles->keys()->values(),       // nombres de roles
            // 'permissions' => $user->getAllPermissions()->pluck('name')
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
    Route::get('/users-consult/{dni}', [UserController::class, 'consultUsers']);
    Route::get('/users-roles', [UserController::class, 'getRoles']);
    Route::post('/users-create', [UserController::class, 'createUser']);
    Route::put('/users-update', [UserController::class, 'updateUser']);
    Route::delete('/users-delete/{id}', [UserController::class, 'destroy']);
    Route::put('/users-update-roles', [UserController::class, 'updateUserRoles']);
    //Route::post('/importUser', [UserController::class, 'importUsersSilucia']);
    Route::post('/importControlador', [UserController::class, 'importControladorSilucia']);

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
    Route::post('services/liquidar-servicio/{serviceId}', [ServiceController::class, 'liquidarServicio']);
    Route::post('/services/{id}/generate-request', [ServiceController::class, 'generateRequest']);
    Route::post('/services/{id}/generate-auth', [ServiceController::class, 'generateAuth']);
    Route::post('/services/{id}/generate-liquidation', [ServiceController::class, 'generateLiquidation']);
    Route::get('/services/idmeta/{mechanicalId}', [ServiceController::class, 'getIdmeta']);
    Route::put('/services/idmeta/', [ServiceController::class, 'updateIdmeta']);

    //daily work log routes
    Route::get('/daily-work-log/{id}', [DailyPartController::class, 'index']);
    Route::post('/daily-work-log', [DailyPartController::class, 'store']);
    Route::put('/daily-work-log', [DailyPartController::class, 'update']);
    Route::delete('/daily-work-log-delete/{id}', [DailyPartController::class, 'destroy']);
    Route::delete('/daily-service-delete/{id}', [DailyPartController::class, 'destroyService']);
    Route::post('/daily-work-log/complete', [DailyPartController::class, 'completeWork']);
    Route::post('/daily-work-log/{id}/generate-pdf', [DailyPartController::class, 'generatePdf']);
    Route::get('/daily-work-document/{WorkLogId}/{date?}', [DailyPartController::class, 'getDocumentWokLog']);

    //document
    Route::post('/daily-work-document/send', [DocumentController::class, 'sendDocument']);
    Route::get('/document-signature/{documentId}', [DocumentController::class, 'getDocumentSignature']);
    Route::get('/documents-signature/pending', [DocumentController::class, 'getPendingDocuments']);
    Route::post('/document-return/resend-to-controller', [DocumentController::class, 'resendDocument']);
    Route::get('/document-userRole', [DocumentController::class, 'getRoles']);

    //mechanical equipment
    Route::get('/mechanical-equipment', [MechanicalEquipmentController::class, 'index']);
    Route::post('/mechanical-equipment', [MechanicalEquipmentController::class, 'store']);
    Route::put('/mechanical-equipment', [MechanicalEquipmentController::class, 'update']);
    Route::delete('/mechanical-equipment/{id}', [MechanicalEquipmentController::class, 'destroy']);

    //products
    Route::get('/products-select', [ProductController::class, 'consultaProductSelect']);

    //shifts
    Route::get('/shifts-select', [ShiftsController::class, 'consultaShifts']);

    //evendence
    Route::get('/daily-work-evendece/{serviceId}', [EvidenceController::class, 'getEvidence']);
});
