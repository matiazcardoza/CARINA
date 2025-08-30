<?php

use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\MechanicalEquipmentController;
use App\Http\Controllers\MovementKardexController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\OrderProductoController;
use App\Http\Controllers\OrderProductsController;
use App\Http\Controllers\PdfControllerKardex;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProductMovementKardexController;
use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    

    //orders silucia routes
    Route::post('orders-silucia/import-order', [OrderSiluciaController::class, 'importOrder']);

    //Services
    Route::get('/services', [ServiceController::class, 'index']);

    //daily work log routes
    Route::get('/daily-work-log/{id}', [DailyPartController::class, 'index']);
    Route::post('/daily-work-log', [DailyPartController::class, 'store']);
    Route::put('/daily-work-log/{id}', [DailyPartController::class, 'update']);
    Route::delete('/daily-work-log/{id}', [DailyPartController::class, 'destroy']);
    Route::post('/daily-work-log/complete', [DailyPartController::class, 'completeWork']);
    Route::post('/daily-work-log/{id}/generate-pdf', [DailyPartController::class, 'generatePdf']);

    //mechanical equipment
    Route::get('/mechanical-equipment', [MechanicalEquipmentController::class, 'index']);
    Route::post('/mechanical-equipment', [MechanicalEquipmentController::class, 'store']);
    Route::put('/mechanical-equipment/{id}', [MechanicalEquipmentController::class, 'update']);
    Route::delete('/mechanical-equipment/{id}', [MechanicalEquipmentController::class, 'destroy']);

    // recurso anidado se obtiene productos pertenecientes a una orden sillucia
    Route::apiResource('orders-silucia.products', OrderProductsController::class)
        ->parameters([
            'orders-silucia' => 'order_silucia'
        ])
        ->only(['index','store'])
        ->shallow();

    //orders producto routes
    Route::get('/products/{product}/movements-kardex', [ProductMovementKardexController::class, 'index']);

    Route::post('/products/{product}/kardex',[MovementKardexController::class, 'storeForProduct']);
    

    /** 
     * Lo usamos para registrar un movimiento y al mismo tiempo registrar en productos los datos obtenidoso de la enpodint de silucia,
     * y el registro ya existe en producto, solemente se asigna un nuevo movimiento
     */
    // 1 movimiento
    // Route::post('/kardex/movements/bulk', [MovementKardexController::class, 'bulk']);   // varios movimientos
});

    // Route::get('/products/{product}/movements-kardex/pdf',[ProductMovementKardexController::class, 'pdf']);
    // Route::post('/movements-kardex', [MovementKardexController::class, 'store']); 

    // =============================================================================================================================================== 
    // buscamos el producto, si no lo encontramos lo creamos y al mismo tiempo guardarmos el movimiento
    Route::post('/movements-kardex', [MovementKardexController::class, 'store']);  
    // mostramos todos los movimientos que pertenecen a un producto de la base de datos de silucia
    Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex',  [MovementKardexController::class, 'indexBySiluciaIds']);
    // generamos un reporte de  todos los movimientos que pertenecen a un producto de la base de datos de silucia
    Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex/pdf',  [MovementKardexController::class, 'pdf']);


    Route::get('/people/{dni}', [PeopleController::class, 'showOrFetch']); // cache-first (db) → RENIEC
    Route::get('/movements-kardex/{movement}/people', [MovementKardexController::class, 'people']);
    Route::post('/movements-kardex/{movement}/people', [MovementKardexController::class, 'attachPerson']);
    Route::delete('/movements-kardex/{movement}/people/{dni}', [MovementKardexController::class, 'detachPerson']);