<?php

use App\Http\Controllers\DailyPartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //daily work log routes
    Route::get('/daily-work-log', [DailyPartController::class, 'index']);
});