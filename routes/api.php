<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoodsReceiptController;
use App\Http\Controllers\Api\InstallationOrderController;
use App\Http\Controllers\Api\Options\BrandsOptionsController;
use App\Http\Controllers\Api\Options\CategoriesOptionsController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', fn () => response()->json(['ok' => true]));
    Route::get('ping', PingController::class);

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me'])
                ->name('auth.me');
            Route::post('logout', [AuthController::class, 'logout'])
                ->name('auth.logout');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', fn (Request $request) => $request->user());

        // Catalog routes
        Route::get('products/meta', [ProductController::class, 'meta'])->name('products.meta');
        Route::get('brands/options', BrandsOptionsController::class)->name('brands.options');
        Route::apiResource('brands', BrandController::class);
        Route::get('categories/options', CategoriesOptionsController::class)->name('categories.options');
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/image', [ProductController::class, 'uploadImage'])->name('products.image.upload');
        Route::delete('products/{product}/image', [ProductController::class, 'deleteImage'])->name('products.image.delete');
        Route::get('dashboard', DashboardController::class)->name('dashboard.show');

        // Inventory routes
        Route::apiResource('warehouses', WarehouseController::class);
        Route::get('stocks', [StockController::class, 'index']);
        Route::get('stocks/{stock}', [StockController::class, 'show']);
        Route::post('stocks/in', [StockController::class, 'in']);
        Route::post('stocks/out', [StockController::class, 'out']);

        // Sales routes
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/confirm', [OrderController::class, 'confirm']);
        Route::post('orders/{order}/deliver', [OrderController::class, 'deliver']);
        Route::post('orders/{order}/pay', [OrderController::class, 'pay']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);

        // Installation routes
        Route::apiResource('installations', InstallationOrderController::class);
        Route::post('installations/{installation}/status', [InstallationOrderController::class, 'updateStatus']);

        // Procurement routes
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('purchases', PurchaseController::class);
        Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve']);
        Route::apiResource('grn', GoodsReceiptController::class)->only(['index', 'show', 'store']);

        // Returns routes
        Route::prefix('returns')->group(function () {
            Route::apiResource('sales', \App\Http\Controllers\Api\SalesReturnController::class)
                ->only(['index', 'show', 'store', 'destroy'])
                ->parameters(['sales' => 'salesReturn'])
                ->names([
                    'index' => 'sales-returns.index',
                    'show' => 'sales-returns.show',
                    'store' => 'sales-returns.store',
                    'destroy' => 'sales-returns.destroy',
                ]);
            Route::post('sales/{salesReturn}/approve', [\App\Http\Controllers\Api\SalesReturnController::class, 'approve']);

            Route::apiResource('purchases', \App\Http\Controllers\Api\PurchaseReturnController::class)
                ->only(['index', 'show', 'store', 'destroy'])
                ->parameters(['purchases' => 'purchaseReturn'])
                ->names([
                    'index' => 'purchase-returns.index',
                    'show' => 'purchase-returns.show',
                    'store' => 'purchase-returns.store',
                    'destroy' => 'purchase-returns.destroy',
                ]);
            Route::post('purchases/{purchaseReturn}/approve', [\App\Http\Controllers\Api\PurchaseReturnController::class, 'approve']);
        });

        // Audit routes
        Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);
    });
});
