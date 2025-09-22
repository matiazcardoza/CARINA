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
use App\Http\Controllers\ObrasController;
use App\Http\Controllers\SignaturesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserObrasController;
use App\Models\SignatureFlow;
use App\Models\SignatureStep;
use App\Models\User;

// use App\Http\Controllers\PdfControllerKardex;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Controllers\OrderProductoController;
// use Illuminate\Support\Facades\Storage;

Route::post('/document-signature/{documentId}', [SignatureController::class, 'storeSignature']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
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


// ROUTAS DE SEGUNDA VERSION DEL MOVIMIENTOS DE ALMACEN - TENANT
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me/obras', [ObrasController::class,'mine']);
    Route::get('/accounts', [UserController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->prefix('/admin')->group(function () {
  Route::get('/users', [UserIndexController::class, 'index']);
  Route::get('/obras', [ObraIndexController::class, 'index']);
  Route::get('/obras/{obra}/miembros', [MembersController::class,'index']);     // devuelve todos los usuario miembros de esta obra
  Route::post('/obras/{obra}/miembros', [MembersController::class,'upsert']);   // asigna user + roles por obra
  Route::delete('/obras/{obra}/miembros/{user}', [MembersController::class,'destroy']);
});

// recibe pdf firmado por firma perú
Route::post('signatures/callback', [SignatureController::class, 'store']);
// Routa que sirve solamente para retornar pdfs, los valores se envian en el formato query params
Route::get('/files-download', [SignatureController::class, 'filesDownload']);

Route::middleware(['auth:sanctum','resolve.obra'])->group(function () {
    // Route::middleware(['resolve.obra'])->group(function () {
    Route::get('/ordenes-compra', [OCController::class,'index']);
    // retora todas los itempecosas a partir de una obra / meta
    Route::get('/pecosas', [PecosaController::class,'getPecosasByWorks']);
    Route::get('/ordenes-compra/{orden}/pecosas', [OCController::class, 'pecosas']);  // nuevo
    //   Route::get('/ordenes-compra', [ObrasController::class,'index']);
    Route::post('/items/{item}/movements', [MovementController::class,'store']);
    // devuelve todos los item pecosas de una obra en especifio
    Route::get('/obras/{obra}/item-pecosas', [PecosaController::class, 'testPecosas'])->middleware(['role:almacen.operador']);
    // registra en la base de datos el movimiento hecho por el usuario
    Route::post('/kardex-movements/{itemPecosa}', [MovementKardexController::class, 'store'])->middleware(['role:almacen.operador']);
    // me devuelve todos los movimmientos de un itemPecosa
    Route::get('/item-pecosas/{itemPecosa}/movements-kardex',   [PecosaController::class, 'getItemPecosas'])->middleware(['role:almacen.operador']);
    // http://localhost:8000/api/item-pecosas/002028/movements-kardex/pdf
    Route::get( '/item-pecosas/{itemPecosa}/movements-kardex/pdf',  [MovementKardexController::class, 'pdf'])->middleware(['role:almacen.operador']);
    // obtenemos los datos de una persona de la api de reniec
    Route::get('/people/{dni}', [PeopleController::class, 'showOrFetch'])->middleware(['role:almacen.operador']); // cache-first (db) → RENIEC
});

Route::get('get-roles-by-scope', function(){
    // Fijar el team/obra actual
    setPermissionsTeamId(2);

    // Obtener el usuario
    $user = User::findOrFail(1);
    // return $user;
    // Limpiar relaciones cacheadas para que se recarguen con el nuevo team
    $user->unsetRelation('roles')->unsetRelation('permissions');

    // Obtener roles del usuario en este team/obra
    return $user->roles;
});

// nuevas rutas para actualizar los permisos por obra - inicio
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // catálogos para el front
    Route::get('/obras',            [AdminCatalogController::class, 'obras']);   // lista todas las obras
    Route::get('/roles',            [AdminCatalogController::class, 'roles']);   // lista de roles (nombres)

    // gestión de obras y roles por usuario
    Route::get   ('/users/{user}/obras',                 [UserObrasController::class, 'index']);
    Route::post  ('/users/{user}/obras',                 [UserObrasController::class, 'store']);   // add obra al user
    // NUEVO: importar (crear/actualizar) obra desde externa + asignar al usuario con roles
    Route::post('/users/{user}/obras/import-assign', [UserObrasController::class, 'importAndAssign']);
    Route::delete('/users/{user}/obras/{obra}',          [UserObrasController::class, 'destroy']); // quitar obra

    // roles por obra
    Route::put   ('/users/{user}/obras/{obra}/roles',    [UserObrasController::class, 'syncRoles']);   // reemplazar
    Route::post  ('/users/{user}/obras/{obra}/roles',    [UserObrasController::class, 'attachRoles']); // agregar
    Route::delete('/users/{user}/obras/{obra}/roles',    [UserObrasController::class, 'detachRoles']); // quitar

    //NUEVO: importar/actualizar obra (desde API externa) + importar PECOSAs + asignar al usuario + set roles
    Route::post('/users/{user}/obras/import', [UserObrasController::class, 'importAttachFromExternal']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('fuel-orders', FuelOrderController::class);
    Route::patch('fuel-orders/{fuelOrder}/decision', [FuelOrderController::class, 'decision']);
});


