<?php

return [
    // Orden de proveedores: primero intenta RENIEC, luego Decolecta.
    'providers' => [
        \App\Providers\ReniecProvider::class,
        \App\Providers\DecolectaProvider::class,
        // \App\Providers\ReniecProvider::class,
    ],
];
