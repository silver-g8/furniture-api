<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | ระบุรายการ domain ที่อนุญาตให้เข้าถึง API ในระหว่างการพัฒนา
    | สามารถเพิ่ม/แก้ไขค่าในไฟล์ .env ผ่านตัวแปร CORS_ALLOWED_ORIGINS
    |
    */

    'paths' => [
        'api/*',
        'dashboard',
        'dashboard/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(
        explode(
            ',',
            env('CORS_ALLOWED_ORIGINS', 'http://localhost:9000,http://127.0.0.1:9000')
        )
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
