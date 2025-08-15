<?php

use App\Http\Controllers\DailyPartController;
use App\Models\DailyPart;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\OrderProductoController;
use App\Http\Controllers\OrderProductsController;
use App\Http\Controllers\ProductMovementKardexController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //daily work log routes
    Route::get('/daily-work-log', [DailyPartController::class, 'index']);
    Route::post('/daily-work-log', [DailyPartController::class, 'store']);
    Route::put('/daily-work-log/{id}', [DailyPartController::class, 'update']);
    Route::delete('/daily-work-log/{id}', [DailyPartController::class, 'destroy']);
    Route::post('/daily-work-log/complete', [DailyPartController::class, 'completeWork']);
    // Route::post('/example', function (Request $request) {
    
    //     $datos = [
    //         "valor" => "ejemplo",
    //         "valordos" => "segundoValor",
    //         "valorTres" => "tercerValor"
    //     ];

    //     return response()->json($datos);
    // });
});


    Route::middleware(['auth:sanctum'])->post('/example', function (Request $request) {
        $datos = [
            "valor" => "ejemplo",
            "valordos" => "segundoValor",
            "valorTres" => "tercerValor"
        ];

        return response()->json($datos);
    });

    // Route::middleware(['auth:sanctum'])->get('/example-return-data', function (Request $request) {
    Route::get('/example-return-data', function (Request $request) {
        $datos = [
            "valor" => "ejemplo",
            "valordos" => "segundoValor",
            "valorTres" => "tercerValor"
        ];

        return response()->json($datos);
    });

    Route::get('/orders-silucia', [OrderSiluciaController::class, 'index']);
    // Route::get('/orders-silucia', [OrderSiluciaController::class, 'index']);
    // Route::get('/videos/{video}/comments', [CommentController::class, 'index']);
    Route::get('/orders-silucia/{orderSilucia}/products', [OrderSiluciaController::class, 'OrderProductsController']);
    Route::post('/orders-silucia/{orderSilucia}/products', [OrderProductsController::class, 'store']);

    // Route::get('/product/{product}/movement-kardex',[ProductMovementKardex::class, 'index']);
    Route::get('/products/{product}/movements-kardex', [ProductMovementKardexController::class, 'index']);

            // http://localhost:4200/api/products/1/movement-kardex
