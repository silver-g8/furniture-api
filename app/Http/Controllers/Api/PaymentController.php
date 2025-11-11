<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::with(['order.customer'])
            ->when(request('order_id'), fn ($q, $orderId) => $q->where('order_id', $orderId))
            ->when(request('method'), fn ($q, $method) => $q->where('method', $method))
            ->latest('paid_at')
            ->paginate(15);

        return response()->json($payments);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['order.customer', 'order.items.product']);

        return response()->json($payment);
    }
}
