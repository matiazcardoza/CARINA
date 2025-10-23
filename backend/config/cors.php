<?php

return [

    'paths' => ['api/*', 'storage/*', 'login', 'logout', 'register', 'user', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:4200', 'http://192.168.100.31:4200')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
