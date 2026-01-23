<?php

return [

    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 'allowed_origins' => [
    //     'http://localhost:5173',
    //     'https://localhost:5173',
    //     'http://localhost:3000',
    //     'https://localhost:3000',
    //     'http://wpz.datainovate.com',
    //     'https://wpz.datainovate.com',
    //     'http://wpz-pos.datainovate.com',
    //     'https://wpz-pos.datainovate.com',
    //     'https://wpz-test.datainovate.com',
    // ],
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
