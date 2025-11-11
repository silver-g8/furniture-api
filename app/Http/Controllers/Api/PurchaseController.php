<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PurchaseStoreRequest;
use App\Http\Requests\Procurement\PurchaseUpdateRequest;
use App\Models\Purchase;
use App\Services\Procurement\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Purchase::class);

        $query = Purchase::with(['supplier', 'items.product']);

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by notes
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('notes', 'like', "%{$search}%");
        }

        $perPage = min($request->input('per_page', 15), 100);
        $purchases = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $purchases->items(),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
                'last_page' => $purchases->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Purchase::class);

        $purchase = $this->purchaseService->createPurchase($request->validated());

        return response()->json([
            'data' => $purchase,
            'message' => 'Purchase created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase): JsonResponse
    {
        $this->authorize('view', $purchase);

        $purchase->load(['supplier', 'items.product', 'goodsReceipt']);

        return response()->json([
            'data' => $purchase,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PurchaseUpdateRequest $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        if (! $purchase->canBeEdited()) {
            return response()->json([
                'message' => 'Cannot edit an approved purchase',
            ], 422);
        }

        $purchase = $this->purchaseService->updatePurchase($purchase, $request->validated());

        return response()->json([
            'data' => $purchase,
            'message' => 'Purchase updated successfully',
        ]);
    }

    /**
     * Approve a purchase.
     */
    public function approve(Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        if (! $purchase->canBeApproved()) {
            return response()->json([
                'message' => 'Purchase cannot be approved. Ensure it has items and is in draft status.',
            ], 422);
        }

        $purchase = $this->purchaseService->approve($purchase);

        return response()->json([
            'data' => $purchase,
            'message' => 'Purchase approved successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase): JsonResponse
    {
        $this->authorize('delete', $purchase);

        if (! $purchase->isDraft()) {
            return response()->json([
                'message' => 'Cannot delete an approved purchase',
            ], 422);
        }

        // Check if goods receipt exists
        if ($purchase->goodsReceipt()->exists()) {
            return response()->json([
                'message' => 'Cannot delete purchase with goods receipt',
            ], 422);
        }

        $purchase->delete();

        return response()->json([
            'message' => 'Purchase deleted successfully',
        ]);
    }
}
