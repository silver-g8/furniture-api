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

        $query = Stock::with(['warehouse', 'product']);

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filter by minimum quantity
        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->input('min_quantity'));
        }

        $perPage = min($request->input('per_page', 15), 100);
        $stocks = $query->paginate($perPage);

        return response()->json([
            'data' => $stocks->items(),
            'meta' => [
                'current_page' => $stocks->currentPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
                'last_page' => $stocks->lastPage(),
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
