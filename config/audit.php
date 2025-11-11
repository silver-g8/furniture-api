<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Queue Toggle
    |--------------------------------------------------------------------------
    |
    | When enabled, audit logs will be written asynchronously via queue jobs.
    | This improves performance for high-traffic operations but requires a
    | queue worker to be running.
    |
    */
    'queue' => env('AUDIT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Type Aliases
    |--------------------------------------------------------------------------
    |
    | Map short type aliases to fully qualified class names for API filtering.
    | These must match the morphMap configuration in AppServiceProvider.
    |
    */
    'type_aliases' => [
        'sales_order' => \App\Models\SalesOrder::class,
        'purchase' => \App\Models\Purchase::class,
        'grn' => \App\Models\GoodsReceipt::class,
        'installation' => \App\Models\InstallationOrder::class,
        'sales_return' => \App\Models\SalesReturn::class,
        'purchase_return' => \App\Models\PurchaseReturn::class,
    ],
];
