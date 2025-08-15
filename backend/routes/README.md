para la api :

    Route::apiResource('orders-silucia.products', OrderProductsController::class)
        ->parameters([
            'orders-silucia' => 'order_silucia'
        ])
        ->only(['index','store'])
        ->shallow();

tener en cuenta:
    laravel infiere que entre el padre "orders-silucia" y el hijo "products" existe un parametro dinamico que es el singular
    del recurso padre: 
        /orders-silucia/{order_silucia}/products
    pero el conversor del plural al sigular de laravel no majena correctamente todos los casos, por este motivo
    se debe indica explicitamente cual es el parametro dinamico para el recurso (en este caso el recurso padre) con el metodo "parameters"