<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockMovementRequest;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Stock::class);

        $query = Stock::with(['warehouse', 'product', 'movements' => function ($q) {
            $q->latest()->limit(1);
        }]);

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Filter by warehouse code
        if ($request->filled('warehouse_code')) {
            $query->whereHas('warehouse', function ($q) use ($request) {
                $q->where('code', $request->input('warehouse_code'));
            });
        }

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

        // Filter by product name
        if ($request->filled('product_name')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->input('product_name').'%');
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
        $stocks = $query->orderBy('warehouse_id')->orderBy('product_id')->paginate($perPage);

        // Transform data
        $transformedData = $stocks->getCollection()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'warehouse_id' => $stock->warehouse_id,
                'product_id' => $stock->product_id,
                'quantity' => $stock->quantity,
                'warehouse' => [
                    'id' => $stock->warehouse->id,
                    'code' => $stock->warehouse->code,
                    'name' => $stock->warehouse->name,
                    'is_active' => $stock->warehouse->is_active,
                ],
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
                'created_at' => $stock->created_at,
                'updated_at' => $stock->updated_at,
            ];
        });

        // Calculate summary
        $summary = [
            'total_warehouses' => Stock::distinct('warehouse_id')->count('warehouse_id'),
            'total_products' => Stock::distinct('product_id')->count('product_id'),
            'total_quantity' => Stock::sum('quantity'),
            'products_with_stock' => Stock::where('quantity', '>', 0)->distinct('product_id')->count('product_id'),
            'products_zero_stock' => Stock::where('quantity', 0)->distinct('product_id')->count('product_id'),
        ];

        return response()->json([
            'data' => $transformedData,
            'meta' => [
                'current_page' => $stocks->currentPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
                'last_page' => $stocks->lastPage(),
                'from' => $stocks->firstItem(),
                'to' => $stocks->lastItem(),
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Get stock summary grouped by warehouse.
     */
    public function summaryByWarehouse(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Stock::class);

        $query = \App\Models\Warehouse::where('is_active', true);

        // Get warehouses with stock summary
        $warehouses = $query->with('stocks.product')->get();

        $summaryData = $warehouses->map(function ($warehouse) {
            $stocks = $warehouse->stocks;

            $totalProducts = $stocks->count();
            $totalQuantity = $stocks->sum('quantity');
            $totalValue = $stocks->sum(function ($stock) {
                return $stock->quantity * (float) $stock->product->price;
            });

            $productsWithStock = $stocks->where('quantity', '>', 0)->count();
            $productsZeroStock = $stocks->where('quantity', 0)->count();

            // Get top 5 products by quantity
            $topProducts = $stocks->sortByDesc('quantity')
                ->take(5)
                ->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product' => [
                            'id' => $stock->product->id,
                            'sku' => $stock->product->sku,
                            'name' => $stock->product->name,
                        ],
                        'quantity' => $stock->quantity,
                    ];
                })
                ->values();

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse' => [
                    'id' => $warehouse->id,
                    'code' => $warehouse->code,
                    'name' => $warehouse->name,
                    'is_active' => $warehouse->is_active,
                ],
                'total_products' => $totalProducts,
                'total_quantity' => $totalQuantity,
                'total_value' => round($totalValue, 2),
                'products_with_stock' => $productsWithStock,
                'products_zero_stock' => $productsZeroStock,
                'top_products' => $topProducts,
            ];
        });

        // Calculate grand totals
        $grandTotalQuantity = $summaryData->sum('total_quantity');
        $grandTotalValue = $summaryData->sum('total_value');

        return response()->json([
            'data' => $summaryData,
            'meta' => [
                'total_warehouses' => $summaryData->count(),
                'grand_total_quantity' => $grandTotalQuantity,
                'grand_total_value' => round($grandTotalValue, 2),
            ],
        ]);
    }

    /**
     * Stock IN operation - Add stock quantity.
     */
    public function in(StockMovementRequest $request): JsonResponse
    {
        $this->authorize('create', Stock::class);

        $validated = $request->validated();

        // Find or create stock record
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $validated['product_id'],
            ],
            ['quantity' => 0]
        );

        // Update stock quantity
        $stock->increment('quantity', $validated['quantity']);

        // Create movement record
        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'in',
            'quantity' => $validated['quantity'],
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $stock->load(['warehouse', 'product']);

        return response()->json([
            'data' => $stock,
            'message' => 'Stock added successfully',
        ], 201);
    }

    /**
     * Stock OUT operation - Remove stock quantity.
     */
    public function out(StockMovementRequest $request): JsonResponse
    {
        $this->authorize('create', Stock::class);

        $validated = $request->validated();

        // Find stock record
        $stock = Stock::where('warehouse_id', $validated['warehouse_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if (! $stock) {
            return response()->json([
                'message' => 'Stock not found for this warehouse and product',
            ], 404);
        }

        // Check if sufficient stock available
        if ($stock->quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock quantity',
                'available' => $stock->quantity,
                'requested' => $validated['quantity'],
            ], 422);
        }

        // Update stock quantity
        $stock->decrement('quantity', $validated['quantity']);

        // Create movement record
        StockMovement::create([
            'stock_id' => $stock->id,
            'type' => 'out',
            'quantity' => $validated['quantity'],
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $stock->load(['warehouse', 'product']);

        return response()->json([
            'data' => $stock,
            'message' => 'Stock removed successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Stock $stock): JsonResponse
    {
        $this->authorize('view', $stock);

        $stock->load(['warehouse', 'product', 'movements.user']);

        return response()->json([
            'data' => $stock,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stock $stock): JsonResponse
    {
        $this->authorize('update', $stock);

        return response()->json([
            'message' => 'Direct stock updates are not allowed. Use IN/OUT operations.',
        ], 403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stock $stock): JsonResponse
    {
        $this->authorize('delete', $stock);

        return response()->json([
            'message' => 'Stock deletion is not allowed. Use OUT operation to reduce stock.',
        ], 403);
    }
}
