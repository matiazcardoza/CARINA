<?php

use App\Http\Controllers\AdminCatalogController;
use App\Http\Controllers\ObraIndexController;
// use App\Http\Controllers\Admin\UserIndexController;
use App\Http\Controllers\UserIndexController;
use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MechanicalEquipmentController;
use App\Http\Controllers\MovementKardexController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\OrderProductsController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMovementKardexController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SignatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OCController;
use App\Models\Service;

use App\Http\Controllers\PecosaController;
use App\Http\Controllers\FuelOrderController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\ObraImportUsersController;
use App\Http\Controllers\ObrasController;
use App\Http\Controllers\SignaturesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserObrasController;
use App\Models\SignatureFlow;
use App\Models\SignatureStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// use App\Http\Controllers\PdfControllerKardex;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Controllers\OrderProductoController;
// use Illuminate\Support\Facades\Storage;

Route::post('/document-signature/{documentId}', [SignatureController::class, 'storeSignature']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        setPermissionsTeamId(1);
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
        setPermissionsTeamId(1);
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

    //daily work log routes
    Route::get('/daily-work-log/{id}', [DailyPartController::class, 'index']);
    Route::post('/daily-work-log', [DailyPartController::class, 'store']);
    Route::put('/daily-work-log', [DailyPartController::class, 'update']);
    Route::delete('/daily-work-log/{id}', [DailyPartController::class, 'destroy']);
    Route::post('/daily-work-log/complete', [DailyPartController::class, 'completeWork']);
    Route::post('/daily-work-log/{id}/generate-pdf', [DailyPartController::class, 'generatePdf']);
    Route::get('/daily-work-document/{WorkLogId}', [DailyPartController::class, 'getDocumentWokLog']);

    //document
    Route::post('/daily-work-document/send', [DocumentController::class, 'sendDocument']);
    Route::get('/documents-signature/pending', [DocumentController::class, 'getPendingDocuments']);
    Route::get('/document-userRole', [DocumentController::class, 'getRoles']);

    //mechanical equipment
    Route::get('/mechanical-equipment', [MechanicalEquipmentController::class, 'index']);
    Route::post('/mechanical-equipment', [MechanicalEquipmentController::class, 'store']);
    Route::put('/mechanical-equipment', [MechanicalEquipmentController::class, 'update']);
    Route::delete('/mechanical-equipment/{id}', [MechanicalEquipmentController::class, 'destroy']);

    //products
    Route::get('/products-select', [ProductController::class, 'consultaProductSelect']);

    //evendence
    Route::get('/daily-work-evendece/{serviceId}', [EvidenceController::class, 'getEvidence']);

    // recurso anidado se obtiene productos pertenecientes a una orden sillucia
    Route::apiResource('orders-silucia.products', OrderProductsController::class)
        ->parameters([
            'orders-silucia' => 'order_silucia'
        ])
        ->only(['index','store'])
        ->shallow();

});


// RUTAS DE SEGUNDA VERSION DEL MOVIMIENTOS DE ALMACEN - TENANT
Route::middleware(['auth:sanctum'])->group(function () {            
   Route::get('me/obras', [ObrasController::class,'mine']);
});

Route::middleware(['auth:sanctum','resolve.obra', 'permission:almacen.access_kardex_management'])->group(function () {
// Route::middleware(['auth:sanctum','resolve.obra', 'role:almacen.residente'])->group(function () {
    Route::get('obras/{obra}/item-pecosas', [PecosaController::class, 'testPecosas']);
    Route::post('kardex-movements/{itemPecosa}', [MovementKardexController::class, 'store'])->middleware(['permission:almacen.create_new_movement']);
    Route::get('item-pecosas/{itemPecosa}/movements-kardex', [PecosaController::class, 'getItemPecosas']);
    Route::get('item-pecosas/{itemPecosa}/movements-kardex/pdf', [MovementKardexController::class, 'pdf'])->middleware(['permission:almacen.generate_report']);
    Route::delete('reports/{report}', [MovementKardexController::class, 'destroy'])->middleware(['permission:almacen.delete_report']);
    Route::get('people/{dni}', [PeopleController::class, 'show'])->middleware(['permission:almacen.create_operator']); 
    Route::get('people-save/{dni}', [PeopleController::class, 'save'])->middleware(['permission:almacen.create_operator']); 
    Route::get('users-operarios', [UserController::class, 'operarios']);
    Route::get('roles-by-obra', [UserObrasController::class, 'userRolesByObra']);
});

Route::middleware(['auth:sanctum','resolve.default.obra'])->prefix('admin')->group(function () {
    Route::get('accounts', [UserController::class, 'index'])->middleware(['role:almacen.superadmin']);
    Route::get('users/{user}/obras', [UserObrasController::class, 'index'])->middleware(['role:almacen.superadmin']);
    Route::get('obras', [AdminCatalogController::class, 'obras'])->middleware(['role:almacen.superadmin']);   
    Route::get('roles', [AdminCatalogController::class, 'roles'])->middleware(['role:almacen.superadmin']);   
    Route::delete('users/{user}/obras/{obra}', [UserObrasController::class, 'destroy'])->middleware(['role:almacen.superadmin']);
    Route::put('users/{user}/obras/{obra}/roles', [UserObrasController::class, 'syncRoles'])->middleware(['role:almacen.superadmin']);
    Route::post('users/{user}/obras/import', [UserObrasController::class, 'importAttachFromExternal'])->middleware(['role:almacen.superadmin']);
    Route::post('obras/import', [UserObrasController::class, 'importWork'])->middleware(['role:almacen.superadmin']);
    Route::post('obras/{obra}/import-users', [ObraImportUsersController::class, 'getSiluciaUsers'])->middleware(['role:almacen.superadmin']);
    Route::get('get-all-obras', [AdminCatalogController::class, 'allObras'])->middleware(['role:almacen.superadmin']);
});


Route::post('signatures/callback', [SignatureController::class, 'store']);
Route::get('files-download', [SignatureController::class, 'filesDownload']);

Route::get('get-roles-by-scope', function(){
    // Fijar el team/obra actual
    setPermissionsTeamId(3);

    // Obtener el usuario
    $user = User::findOrFail(1);
    // return $user;
    // Limpiar relaciones cacheadas para que se recarguen con el nuevo team
    $user->unsetRelation('roles')->unsetRelation('permissions');

    // Obtener roles del usuario en este team/obra
    return $user->roles;
});

// RUTAS PARA SISTEMA DE VALES DE TRANSPORTE
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('fuel-orders', FuelOrderController::class);
    Route::patch('fuel-orders/{fuelOrder}/decision', [FuelOrderController::class, 'decision']);
});


