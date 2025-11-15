<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\WarehouseStoreRequest;
use App\Http\Requests\Inventory\WarehouseUpdateRequest;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        $query = Warehouse::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or code
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 15), 100);
        $warehouses = $query->paginate($perPage);

        return response()->json([
            'data' => $warehouses->items(),
            'meta' => [
                'current_page' => $warehouses->currentPage(),
                'per_page' => $warehouses->perPage(),
                'total' => $warehouses->total(),
                'last_page' => $warehouses->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WarehouseStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = Warehouse::create($request->validated());

        return response()->json([
            'data' => $warehouse,
            'message' => 'Warehouse created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        $warehouse->load('stocks.product');

        return response()->json([
            'data' => $warehouse,
        ]);
    }

    /**
     * Get stocks for a specific warehouse.
     */
    public function stocks(Warehouse $warehouse, Request $request): JsonResponse
    {
        $this->authorize('view', $warehouse);

        $query = $warehouse->stocks()->with(['product', 'movements' => function ($q) {
            $q->latest()->limit(1);
        }]);

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filter by product SKU
        if ($request->filled('product_sku')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sku', $request->input('product_sku'));
            });
        }

        // Filter by minimum quantity
        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->input('min_quantity'));
        }

        // Filter by has stock (quantity > 0)
        if ($request->has('has_stock')) {
            if ($request->boolean('has_stock')) {
                $query->where('quantity', '>', 0);
            } else {
                $query->where('quantity', '<=', 0);
            }
        }

        // Filter by zero stock
        if ($request->has('zero_stock')) {
            if ($request->boolean('zero_stock')) {
                $query->where('quantity', 0);
            }
        }

        $perPage = min($request->input('per_page', 15), 100);
        $stocks = $query->orderBy('product_id')->paginate($perPage);

        // Transform data
        $transformedData = $stocks->getCollection()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'quantity' => $stock->quantity,
                'product' => [
                    'id' => $stock->product->id,
                    'sku' => $stock->product->sku,
                    'name' => $stock->product->name,
                    'price' => $stock->product->price,
                ],
                'last_movement' => $stock->movements->first() ? [
                    'id' => $stock->movements->first()->id,
                    'type' => $stock->movements->first()->type,
                    'quantity' => $stock->movements->first()->quantity,
                    'created_at' => $stock->movements->first()->created_at,
                ] : null,
            ];
        });

        // Calculate summary for this warehouse
        $allWarehouseStocks = $warehouse->stocks;
        $summary = [
            'total_products' => $allWarehouseStocks->count(),
            'total_quantity' => $allWarehouseStocks->sum('quantity'),
            'products_with_stock' => $allWarehouseStocks->where('quantity', '>', 0)->count(),
            'products_zero_stock' => $allWarehouseStocks->where('quantity', 0)->count(),
        ];

        return response()->json([
            'data' => [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'code' => $warehouse->code,
                    'name' => $warehouse->name,
                    'is_active' => $warehouse->is_active,
                ],
                'stocks' => $transformedData,
                'summary' => $summary,
            ],
            'meta' => [
                'current_page' => $stocks->currentPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
                'last_page' => $stocks->lastPage(),
                'from' => $stocks->firstItem(),
                'to' => $stocks->lastItem(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WarehouseUpdateRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return response()->json([
            'data' => $warehouse,
            'message' => 'Warehouse updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        // Check if warehouse has stocks
        if ($warehouse->stocks()->exists()) {
            return response()->json([
                'message' => 'Cannot delete warehouse with existing stocks',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse deleted successfully',
        ]);
    }
}
