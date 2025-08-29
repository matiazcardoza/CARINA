<?php

use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\MechanicalEquipmentController;
use App\Http\Controllers\MovementKardexController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\OrderProductoController;
use App\Http\Controllers\OrderProductsController;
use App\Http\Controllers\PdfControllerKardex;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMovementKardexController;
use App\Http\Controllers\SignatureCallbackController;
use App\Http\Controllers\SignatureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
    
});

Route::middleware(['auth:sanctum'])->group(function () {
        // ---------------------------Revisar y eliminar estas endopitns con sus metodos----------------------------------
    Route::get('/products/{product}/movements-kardex', [ProductMovementKardexController::class, 'index']);
    // buscamos el producto, si no lo encontramos lo creamos y al mismo tiempo guardarmos el movimiento
    Route::post('/movements-kardex', [MovementKardexController::class, 'store']);  
    // mostramos todos los movimientos que pertenecen a un producto de la base de datos de silucia
    Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex',  [MovementKardexController::class, 'indexBySiluciaIds']);
    // generamos un reporte de  todos los movimientos que pertenecen a un producto de la base de datos de silucia
    Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex/pdf',  [MovementKardexController::class, 'pdf']);
    // obtenemos los productos guardados de nuestra propia base de datos
    Route::get('/products', [ProductController::class, 'index']);
    // ruta para recibir el pdf
    Route::post('signatures/callback', [SignatureController::class, 'store']);
    // ruta que se usra para la generacion de qrs y para la descarga de las mismas
    Route::get('/signatures/{path}', [SignatureController::class, 'exportPdf']);


    // muestra datos de una persona, ya sea consultadno a la api de reniec o consultando la propia base de datos
    Route::get('/people/{dni}', [PeopleController::class, 'showOrFetch']); // cache-first (db) → RENIEC
    // muestra todas las personas pertenecientes a un movimiento
    // Route::get('/movements-kardex/{movement}/people', [MovementKardexController::class, 'people']);
    // esta endpoint debe hacerse en "/movements-kardex" pues ahi es donde se guardara el dato de un 
    Route::post('/movements-kardex/{movement}/people', [MovementKardexController::class, 'attachPerson']);
    // endpoint no terminado - sirve para quitar una persona de un movimiento
    // Route::delete('/movements-kardex/{movement}/people/{dni}', [MovementKardexController::class, 'detachPerson']);
});

