<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure morph map for polymorphic relationships
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'sales_order' => \App\Models\SalesOrder::class,
            'purchase' => \App\Models\Purchase::class,
            'grn' => \App\Models\GoodsReceipt::class,
            'installation' => \App\Models\InstallationOrder::class,
            'sales_return' => \App\Models\SalesReturn::class,
            'purchase_return' => \App\Models\PurchaseReturn::class,
        ]);
    }
}
