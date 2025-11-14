<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Customer;
use App\Services\CustomerPurchaseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CustomerPurchaseController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the products that the customer has purchased.
     */
    public function index(Customer $customer, CustomerPurchaseService $service): JsonResponse
    {
        $this->authorize('view', $customer);

        $products = $service->getPurchasedProducts($customer->id);

        return ProductResource::collection($products)->response();
    }
}
