<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Returns\SalesReturnStoreRequest;
use App\Models\SalesReturn;
use App\Services\Returns\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesReturn::class);

        $query = SalesReturn::with(['order', 'warehouse', 'items.product']);

        // Filter by order
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by reason
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 15), 100);
        $salesReturns = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $salesReturns->items(),
            'meta' => [
                'current_page' => $salesReturns->currentPage(),
                'per_page' => $salesReturns->perPage(),
                'total' => $salesReturns->total(),
                'last_page' => $salesReturns->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SalesReturnStoreRequest $request): JsonResponse
    {
        $this->authorize('create', SalesReturn::class);

        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Create sales return
            $salesReturn = SalesReturn::create([
                'order_id' => $validated['order_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                $salesReturn->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'remark' => $item['remark'] ?? null,
                ]);
            }

            // Calculate totals
            $salesReturn->calculateTotals();

            DB::commit();

            $salesReturn->load(['order', 'warehouse', 'items.product']);

            return response()->json([
                'data' => $salesReturn,
                'message' => 'Sales return created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create sales return',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesReturn $salesReturn): JsonResponse
    {
        $this->authorize('view', $salesReturn);

        $salesReturn->load(['order', 'warehouse', 'items.product']);

        return response()->json([
            'data' => $salesReturn,
        ]);
    }

    /**
     * Approve a sales return.
     */
    public function approve(SalesReturn $salesReturn): JsonResponse
    {
        $this->authorize('update', $salesReturn);

        try {
            $salesReturn = $this->returnService->approveSalesReturn($salesReturn);

            return response()->json([
                'data' => $salesReturn,
                'message' => 'Sales return approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve sales return',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesReturn $salesReturn): JsonResponse
    {
        $this->authorize('delete', $salesReturn);

        if (! $salesReturn->isDraft()) {
            return response()->json([
                'message' => 'Cannot delete an approved sales return',
            ], 422);
        }

        $salesReturn->delete();

        return response()->json([
            'message' => 'Sales return deleted successfully',
        ]);
    }
}
