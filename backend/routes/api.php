<?php

use App\Http\Controllers\DailyPartController;
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
use App\Models\Service;

use App\Http\Controllers\PecosaController;
use App\Http\Controllers\FuelOrderController;
use App\Http\Controllers\SignaturesController;
use App\Http\Controllers\RoleController;
use App\Models\SignatureFlow;
use App\Models\SignatureStep;

// use App\Http\Controllers\PdfControllerKardex;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Controllers\OrderProductoController;
// use Illuminate\Support\Facades\Storage;

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


Route::middleware(['auth:sanctum'])->group(function () {

    // ---------------------------Revisar y eliminar estas endopitns con sus metodos----------------------------------
    Route::get('/products/{product}/movements-kardex', [ProductMovementKardexController::class, 'index'])->middleware(['role:almacen_almacenero']);
    // obtenemos la lista de ordenes de silucia
    Route::get('silucia-orders', [PurchaseOrdersController::class, 'index'])->middleware(['role:almacen_almacenero']);
    // buscamos el producto, si no lo encontramos lo creamos y al mismo tiempo guardarmos el movimiento
    Route::post('/movements-kardex', [MovementKardexController::class, 'store'])->middleware(['role:almacen_almacenero']);

    // mostramos todos los movimientos que pertenecen a un producto de la base de datos de silucia ----- (CAMBIAMOS PARA OBTENER LOS MOVIMIENTOS DE LAS PECOSAS)
    // Route::get( 'silucia-containers/{containerId}/items-pecosas/{itemId}/movements',  [MovementKardexController::class, 'indexBySiluciaIds'])->middleware(['role:almacen_almacenero']);
    Route::get( 'silucia-pecosas/{pecosaId}/items-pecosas/{itemId}/movements',  [MovementKardexController::class, 'indexBySiluciaIds'])->middleware(['role:almacen_almacenero']);

    // generamos un reporte de  todos los movimientos que pertenecen a un producto de la base de datos de silucia ----- (CAMBIAMOS PARA OBTENER LOS MOVIMIENTOS DE LAS PECOSAS)
    // Route::get( 'silucia-containers/{containerId}/items-pecosas/{itemId}/movements/pdf',  [MovementKardexController::class, 'pdf'])->middleware(['role:almacen_almacenero']);
    Route::get( 'silucia-pecosas/{pecosaId}/items-pecosas/{itemId}/movements/pdf',  [MovementKardexController::class, 'pdf'])->middleware(['role:almacen_almacenero']);
    
    // devuelve los productos guardados de nuestra propia base de datos
    Route::get('/items-pecosas', [ProductController::class, 'index']);

    // ******************* refactorizacion de codigo: pasar de datos de ordenes a pecosas - (inicio) *******************
    Route::get('silucia-pecosas', [PecosaController::class, 'index'])->middleware(['role:almacen_almacenero']);

    // muestra datos de una persona, ya sea consultadno a la api de reniec o consultando la propia base de datos
    Route::get('/people/{dni}', [PeopleController::class, 'showOrFetch'])->middleware(['role:almacen_almacenero']); // cache-first (db) → RENIEC
    // muestra todas las personas pertenecientes a un movimiento
    Route::post('/movements-kardex/{movement}/people', [MovementKardexController::class, 'attachPerson'])->middleware(['role:almacen_almacenero']);
    // endpoint no terminado - sirve para quitar una persona de un movimientoa

    // rutas para vales de transporte
    
    // LISTA
    Route::get('/fuel-orders', [FuelOrderController::class, 'index']);

    // GENERAR PDF + FLUJO
    Route::post('/fuel-orders/{order}/generate-report', [FuelOrderController::class, 'generateReport']);

    // ESTADO DE REPORTE/FLUJO
    Route::get('/fuel-orders/{order}/report', [FuelOrderController::class, 'showReport']);

    // DESCARGA PDF
    Route::get('/fuel-orders/{order}/report/download', [FuelOrderController::class, 'downloadReport']);

    // FIRMA (callback genérico)
    Route::post('/signatures/callback', [SignaturesController::class, 'callback']);
});
    // recibe pdf firmado por firma perú
    Route::post('signatures/callback', [SignatureController::class, 'store']);
    // Routa que sirve solamente para retornar pdfs, los valores se envian en el formato query params
    // Route::get('/signatures/{path}', [SignatureController::class, 'exportPdf']);
    // peticion esperada GET /files-download?name=24234234.pdf
    Route::get('/files-download', [SignatureController::class, 'filesDownload']);
    // Pruebas para generar codigo qr
    // Route::get('example-qr', [PdfControllerKardex::class, 'generateQRCcode']);


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('fuel-orders', FuelOrderController::class);
    Route::patch('fuel-orders/{fuelOrder}/decision', [FuelOrderController::class, 'decision']);
});