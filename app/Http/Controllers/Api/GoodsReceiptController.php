<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\GRNStoreRequest;
use App\Models\GoodsReceipt;
use App\Models\Purchase;
use App\Services\Procurement\GRNService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoodsReceiptController extends Controller
{
    public function __construct(
        protected GRNService $grnService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $query = GoodsReceipt::with(['purchase.supplier', 'receivedBy']);

        // Filter by purchase
        if ($request->filled('purchase_id')) {
            $query->where('purchase_id', $request->input('purchase_id'));
        }

        // Filter by received_by
        if ($request->filled('received_by')) {
            $query->where('received_by', $request->input('received_by'));
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('received_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('received_at', '<=', $request->input('to_date'));
        }

        $perPage = min($request->input('per_page', 15), 100);
        $receipts = $query->latest('received_at')->paginate($perPage);

        return response()->json([
            'data' => $receipts->items(),
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'per_page' => $receipts->perPage(),
                'total' => $receipts->total(),
                'last_page' => $receipts->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage (receive goods).
     */
    public function store(GRNStoreRequest $request): JsonResponse
    {
        $this->authorize('create', GoodsReceipt::class);

        $validated = $request->validated();
        /** @var Purchase $purchase */
        $purchase = Purchase::findOrFail($validated['purchase_id']);

        try {
            $grn = $this->grnService->receive($purchase, $validated);

            return response()->json([
                'data' => $grn,
                'message' => 'Goods received successfully',
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('view', $goodsReceipt);

        $goodsReceipt->load([
            'purchase.supplier',
            'purchase.items.product',
            'items.product',
            'items.warehouse',
            'receivedBy',
        ]);

        return response()->json([
            'data' => $goodsReceipt,
        ]);
    }
}
