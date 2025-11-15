<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockMovementIndexRequest;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;

class StockMovementController extends Controller
{
    /**
     * Display a listing of stock movements.
     */
    public function index(StockMovementIndexRequest $request): JsonResponse
    {
        // Use Stock policy for authorization (stock movements are part of stock management)
        $this->authorize('viewAny', Stock::class);

        $query = StockMovement::with([
            'stock.warehouse',
            'stock.product',
            'user:id,name,email',
            'reference',
        ])
        // Exclude records with invalid reference_type (SEEDER_INIT)
        ->where(function ($q) {
            $q->whereNotNull('reference_type')
              ->where('reference_type', '!=', 'SEEDER_INIT')
              ->orWhereNull('reference_type');
        });

        // Apply filters
        if ($request->filled('warehouse_id')) {
            $query->filterByWarehouse($request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->filterByProduct($request->input('product_id'));
        }

        if ($request->filled('stock_id')) {
            $query->filterByStock($request->input('stock_id'));
        }

        if ($request->filled('type')) {
            $query->filterByType($request->input('type'));
        }

        if ($request->filled('reference_type')) {
            $referenceId = $request->input('reference_id');
            $query->filterByReference(
                $request->input('reference_type'),
                $referenceId ? (int) $referenceId : null
            );
        }

        if ($request->filled('user_id')) {
            $query->filterByUser($request->input('user_id'));
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->filterByDateRange(
                $request->input('from_date'),
                $request->input('to_date')
            );
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Order by created_at descending (newest first)
        $query->latest('created_at');

        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $movements = $query->paginate($perPage);

        // Transform data to include balance information
        $transformedData = $movements->getCollection()->map(function ($movement) {
            return [
                'id' => $movement->id,
                'stock_id' => $movement->stock_id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'balance_before' => $movement->balance_before,
                'balance_after' => $movement->balance_after,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'reference' => $movement->reference ? [
                    'id' => $movement->reference->id,
                    'type' => class_basename($movement->reference_type),
                ] : null,
                'user' => $movement->user ? [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                    'email' => $movement->user->email,
                ] : null,
                'stock' => [
                    'id' => $movement->stock->id,
                    'warehouse' => [
                        'id' => $movement->stock->warehouse->id,
                        'code' => $movement->stock->warehouse->code,
                        'name' => $movement->stock->warehouse->name,
                    ],
                    'product' => [
                        'id' => $movement->stock->product->id,
                        'sku' => $movement->stock->product->sku,
                        'name' => $movement->stock->product->name,
                    ],
                ],
                'created_at' => $movement->created_at,
            ];
        });

        return response()->json([
            'data' => $transformedData,
            'meta' => [
                'current_page' => $movements->currentPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'last_page' => $movements->lastPage(),
                'from' => $movements->firstItem(),
                'to' => $movements->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified stock movement.
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        // Use Stock policy for authorization
        $this->authorize('view', $stockMovement->stock);

        $stockMovement->load([
            'stock.warehouse',
            'stock.product',
            'user:id,name,email',
            'reference',
        ]);

        return response()->json([
            'data' => [
                'id' => $stockMovement->id,
                'stock_id' => $stockMovement->stock_id,
                'type' => $stockMovement->type,
                'quantity' => $stockMovement->quantity,
                'balance_before' => $stockMovement->balance_before,
                'balance_after' => $stockMovement->balance_after,
                'reference_type' => $stockMovement->reference_type,
                'reference_id' => $stockMovement->reference_id,
                'reference' => $stockMovement->reference ? [
                    'id' => $stockMovement->reference->id,
                    'type' => class_basename($stockMovement->reference_type),
                    'data' => $stockMovement->reference,
                ] : null,
                'user' => $stockMovement->user ? [
                    'id' => $stockMovement->user->id,
                    'name' => $stockMovement->user->name,
                    'email' => $stockMovement->user->email,
                ] : null,
                'stock' => [
                    'id' => $stockMovement->stock->id,
                    'quantity' => $stockMovement->stock->quantity,
                    'warehouse' => [
                        'id' => $stockMovement->stock->warehouse->id,
                        'code' => $stockMovement->stock->warehouse->code,
                        'name' => $stockMovement->stock->warehouse->name,
                    ],
                    'product' => [
                        'id' => $stockMovement->stock->product->id,
                        'sku' => $stockMovement->stock->product->sku,
                        'name' => $stockMovement->stock->product->name,
                        'price' => $stockMovement->stock->product->price,
                    ],
                ],
                'created_at' => $stockMovement->created_at,
                'updated_at' => $stockMovement->updated_at,
            ],
        ]);
    }
}

