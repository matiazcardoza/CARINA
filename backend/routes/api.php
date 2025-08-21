<?php

use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\MovementKardexController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\OrderProductoController;
use App\Http\Controllers\OrderProductsController;
use App\Http\Controllers\PdfControllerKardex;
use App\Http\Controllers\ProductMovementKardexController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    

    //orders silucia routes
    Route::get('/orders-silucia', [OrderSiluciaController::class, 'index']);
    Route::post('orders-silucia/import-order', [OrderSiluciaController::class, 'importOrder']);

    //daily work log routes
    Route::get('/daily-work-log/{id}', [DailyPartController::class, 'index']);
    Route::post('/daily-work-log', [DailyPartController::class, 'store']);
    Route::put('/daily-work-log/{id}', [DailyPartController::class, 'update']);
    Route::delete('/daily-work-log/{id}', [DailyPartController::class, 'destroy']);
    Route::post('/daily-work-log/complete', [DailyPartController::class, 'completeWork']);
    Route::post('/daily-work-log/{id}/generate-pdf', [DailyPartController::class, 'generatePdf']);

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
    Route::get('/products/{product}/movements-kardex/pdf',[ProductMovementKardexController::class, 'pdf']);

    /** 
     * Lo usamos para registrar un movimiento y al mismo tiempo registrar en productos los datos obtenidoso de la enpodint de silucia,
     * y el registro ya existe en producto, solemente se asigna un nuevo movimiento
     */
         // 1 movimiento
    // Route::post('/kardex/movements/bulk', [MovementKardexController::class, 'bulk']);   // varios movimientos
});

// Route::post('/movements-kardex', [MovementKardexController::class, 'store']);  
Route::post('/movements-kardex', [MovementKardexController::class, 'store']);  
Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex',  [MovementKardexController::class, 'indexBySiluciaIds']);
Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex/pdf',  [MovementKardexController::class, 'pdf']);

