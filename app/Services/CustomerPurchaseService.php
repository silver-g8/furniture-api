<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CustomerPurchaseService
{
    /**
     * Get distinct products that a customer has purchased.
     *
     * @return Collection<int, Product>
     */
    public function getPurchasedProducts(int $customerId): Collection
    {
        return Product::query()
            ->select('products.*')
            ->join('sales_order_items', 'sales_order_items.product_id', '=', 'products.id')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.customer_id', $customerId)
            ->with(['brand', 'category'])
            ->distinct()
            ->get();
    }
}
