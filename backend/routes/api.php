<?php

use App\Http\Controllers\DailyPartController;
use App\Http\Controllers\MovementKardexController;
use App\Http\Controllers\OrderSiluciaController;
use App\Http\Controllers\OrderProductoController;
use App\Http\Controllers\OrderProductsController;
use App\Http\Controllers\PdfControllerKardex;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMovementKardexController;
use App\Http\Controllers\SignatureCallbackController;
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

    // recurso anidado se obtiene productos pertenecientes a una orden sillucia
    Route::apiResource('orders-silucia.products', OrderProductsController::class)
        ->parameters([
            'orders-silucia' => 'order_silucia'
        ])
        ->only(['index','store'])
        ->shallow();


    
    
});


    // ---------------------------Revisar y eliminar estas endopitns con sus metodos----------------------------------
    Route::get('/products/{product}/movements-kardex', [ProductMovementKardexController::class, 'index']);
    // Route::post('/products/{product}/kardex',[MovementKardexController::class, 'storeForProduct']);
    // Route::get('/products/{product}/movements-kardex/pdf',[ProductMovementKardexController::class, 'pdf']);
    // Route::post('/movements-kardex', [MovementKardexController::class, 'store']); 
    // Route::post('/kardex/movements/bulk', [MovementKardexController::class, 'bulk']);   // varios movimientos
    // =============================================================================================================================================== 
    // buscamos el producto, si no lo encontramos lo creamos y al mismo tiempo guardarmos el movimiento
    Route::post('/movements-kardex', [MovementKardexController::class, 'store']);  
    // mostramos todos los movimientos que pertenecen a un producto de la base de datos de silucia
    Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex',  [MovementKardexController::class, 'indexBySiluciaIds']);
    // generamos un reporte de  todos los movimientos que pertenecen a un producto de la base de datos de silucia
    Route::get( 'silucia-orders/{id_order_silucia}/products/{id_product_silucia}/movements-kardex/pdf',  [MovementKardexController::class, 'pdf']);
    // obtenemos los productos guardados de nuestra propia base de datos
    Route::get('/products', [ProductController::class, 'index']);
    // ruta para recibir el pdf
    Route::post('signatures/callback', [SignatureCallbackController::class, 'store']);
    
    // Ruta oficial para entregar archivos
    Route::get('/signatures/{path}', function ($path) {

        // return "hola mundo002";
        $path = 'silucia_product_reports/' . basename($path);
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }
        return Storage::download($path, basename($path));
    });
    // localhost:8000/api/signatures/9/download-current
    // ruta de prueba para probar disponibilidad de archivo
    Route::get('/payments/{path}', function ($path) {
        // return "hola mundo002";
        $path = 'silucia_product_reports/' . basename($path);
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }
        return Storage::download($path, basename($path));
    });
    Route::get('create_new_directory', function(){
        // crea un nuevo directorio, los simbolos slash indican el nivel
        // Storage::disk('local')->makeDirectory('uno/dos/tres/cutro');

        // toma un archivo
        // $document = Storage::disk('local')->get('silucia_product_reports/PDF_NUMERO_1.pdf');

        // descarga un archivo
        //  Storage::disk('public')->download('facturas/factura-001.pdf');
        // $document = Storage::download('silucia_product_reports/PDF_NUMERO_1.pdf');
        // return Storage::download('silucia_product_reports/PDF_NUMERO_1.pdf');

        // copiar y pegar
        // $document = Storage::disk('local')->get('silucia_product_reports/PDF_NUMERO_1.pdf');
        // Storage::disk('local')->put('silucia_product_reports/PDF_NUMERO_1000.pdf',$document);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('get_user_roles', function(){


        // Obtiene el usuario actualmente autenticado
            $user = Auth::user();
            $roleNames = $user->getRoleNames(); // Devuelve algo como: ["admin", "editor"]
            return response()->json($roleNames);

            // return response()->json("hola mundo");

            // return "hola mundo";
            $user = Auth::user();
            
            // return $user;
            // 1. Obtener la colección completa de objetos Role
            $roles = $user->roles;
            return response()->json($roles->getRoleNames());
            return $roles;
            // 2. Si solo necesitas los NOMBRES de los roles (muy común)
            // $roleNames = $user->getRoleNames(); // Devuelve una colección de strings: ej. ['admin', 'editor']
        });
    });

    // muestra datos de una persona, ya sea consultadno a la api de reniec o consultando la propia base de datos
    Route::get('/people/{dni}', [PeopleController::class, 'showOrFetch']); // cache-first (db) → RENIEC
    // muestra todas las personas pertenecientes a un movimiento
    Route::get('/movements-kardex/{movement}/people', [MovementKardexController::class, 'people']);
    // esta endpoint debe hacerse en "/movements-kardex" pues ahi es donde se guardara el dato de un 
    Route::post('/movements-kardex/{movement}/people', [MovementKardexController::class, 'attachPerson']);
    // endpoint no terminado - sirve para quitar una persona de un movimiento
    Route::delete('/movements-kardex/{movement}/people/{dni}', [MovementKardexController::class, 'detachPerson']);