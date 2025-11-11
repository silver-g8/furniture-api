<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Returns\PurchaseReturnStoreRequest;
use App\Models\PurchaseReturn;
use App\Services\Returns\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseReturn::class);

        $query = PurchaseReturn::with(['purchase', 'warehouse', 'items.product']);

        // Filter by purchase
        if ($request->filled('purchase_id')) {
            $query->where('purchase_id', $request->input('purchase_id'));
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
        $purchaseReturns = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $purchaseReturns->items(),
            'meta' => [
                'current_page' => $purchaseReturns->currentPage(),
                'per_page' => $purchaseReturns->perPage(),
                'total' => $purchaseReturns->total(),
                'last_page' => $purchaseReturns->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseReturnStoreRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseReturn::class);

        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Create purchase return
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id' => $validated['purchase_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                $purchaseReturn->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'remark' => $item['remark'] ?? null,
                ]);
            }

            // Calculate totals
            $purchaseReturn->calculateTotals();

            DB::commit();

            $purchaseReturn->load(['purchase', 'warehouse', 'items.product']);

            return response()->json([
                'data' => $purchaseReturn,
                'message' => 'Purchase return created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create purchase return',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $this->authorize('view', $purchaseReturn);

        $purchaseReturn->load(['purchase', 'warehouse', 'items.product']);

        return response()->json([
            'data' => $purchaseReturn,
        ]);
    }

    /**
     * Approve a purchase return.
     */
    public function approve(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $this->authorize('update', $purchaseReturn);

        try {
            $purchaseReturn = $this->returnService->approvePurchaseReturn($purchaseReturn);

            return response()->json([
                'data' => $purchaseReturn,
                'message' => 'Purchase return approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve purchase return',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $this->authorize('delete', $purchaseReturn);

        if (! $purchaseReturn->isDraft()) {
            return response()->json([
                'message' => 'Cannot delete an approved purchase return',
            ], 422);
        }

        $purchaseReturn->delete();

        return response()->json([
            'message' => 'Purchase return deleted successfully',
        ]);
    }
}
