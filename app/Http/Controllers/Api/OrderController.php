<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\OrderStoreRequest;
use App\Http\Requests\Sales\OrderUpdateRequest;
use App\Models\Order;
use App\Services\Sales\OrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with(['customer', 'items.product'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('customer_id'), fn ($q, $customerId) => $q->where('customer_id', $customerId))
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrderStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validated();

        $order = Order::create([
            'customer_id' => $data['customer_id'],
            'status' => 'draft',
            'discount' => $data['discount'] ?? 0,
            'tax' => $data['tax'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        // Create order items
        foreach ($data['items'] as $itemData) {
            $item = $order->items()->create([
                'product_id' => $itemData['product_id'],
                'qty' => $itemData['qty'],
                'price' => $itemData['price'],
                'discount' => $itemData['discount'] ?? 0,
                'total' => 0, // Will be calculated
            ]);

            $this->orderService->recalculateItemTotal($item);
        }

        $order->load(['customer', 'items.product']);

        return response()->json($order, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['customer', 'items.product', 'payments']);

        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrderUpdateRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        if (! $order->canBeModified()) {
            return response()->json([
                'message' => 'Order cannot be modified. Only draft orders can be updated.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validated();

        $order->update([
            'customer_id' => $data['customer_id'] ?? $order->customer_id,
            'discount' => $data['discount'] ?? $order->discount,
            'tax' => $data['tax'] ?? $order->tax,
            'notes' => $data['notes'] ?? $order->notes,
        ]);

        // Update items if provided
        if (isset($data['items'])) {
            // Delete existing items
            $order->items()->delete();

            // Create new items
            foreach ($data['items'] as $itemData) {
                $item = $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'price' => $itemData['price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'total' => 0,
                ]);

                $this->orderService->recalculateItemTotal($item);
            }
        }

        $order->load(['customer', 'items.product']);

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): Response|JsonResponse
    {
        $this->authorize('delete', $order);

        if (! $order->canBeModified()) {
            return response()->json([
                'message' => 'Order cannot be deleted. Only draft orders can be deleted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $order->delete();

        return response()->noContent();
    }

    /**
     * Confirm an order.
     */
    public function confirm(Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        try {
            $this->orderService->validateOrderItems($order);
            $this->orderService->confirm($order);

            $order->load(['customer', 'items.product']);

            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Deliver an order.
     */
    public function deliver(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        try {
            $this->orderService->deliver($order, $request->warehouse_id);

            $order->load(['customer', 'items.product']);

            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Record a payment for an order.
     */
    public function pay(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:32'],
            'paid_at' => ['required', 'date'],
            'ref_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $payment = $this->orderService->recordPayment($order, $validated);

            $order->load(['customer', 'items.product', 'payments']);

            return response()->json([
                'order' => $order,
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
