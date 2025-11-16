<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ap\StoreApPaymentRequest;
use App\Http\Requests\Ap\UpdateApPaymentRequest;
use App\Models\ApPayment;
use App\Services\Ap\ApPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApPaymentController extends Controller
{
    public function __construct(
        protected ApPaymentService $paymentService
    ) {}

    /**
     * Display a listing of AP payments.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApPayment::class);

        $query = ApPayment::with(['supplier', 'allocations.invoice']);

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('payment_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('payment_date', '<=', $request->input('to_date'));
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Search by payment number or note
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('payment_no', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 15), 100);
        $payments = $query->latest('payment_date')->paginate($perPage);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created AP payment.
     */
    public function store(StoreApPaymentRequest $request): JsonResponse
    {
        $this->authorize('create', ApPayment::class);

        $payment = $this->paymentService->createPayment($request->validated());

        return response()->json([
            'data' => $payment,
            'message' => 'AP Payment created successfully',
        ], 201);
    }

    /**
     * Display the specified AP payment.
     */
    public function show(ApPayment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['supplier', 'allocations.invoice']);

        return response()->json([
            'data' => $payment,
        ]);
    }

    /**
     * Update the specified AP payment.
     */
    public function update(UpdateApPaymentRequest $request, ApPayment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        if (!$payment->canBeUpdated()) {
            return response()->json([
                'message' => 'Cannot update a posted payment',
            ], 422);
        }

        $payment = $this->paymentService->updatePayment($payment, $request->validated());

        return response()->json([
            'data' => $payment,
            'message' => 'AP Payment updated successfully',
        ]);
    }

    /**
     * Post a payment.
     */
    public function post(ApPayment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        if (!$payment->canBePosted()) {
            return response()->json([
                'message' => 'Payment cannot be posted. Ensure it has allocations.',
            ], 422);
        }

        $payment = $this->paymentService->postPayment($payment);

        return response()->json([
            'data' => $payment,
            'message' => 'AP Payment posted successfully',
        ]);
    }

    /**
     * Cancel a payment.
     */
    public function cancel(ApPayment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        if (!$payment->canBeCancelled()) {
            return response()->json([
                'message' => 'Payment cannot be cancelled',
            ], 422);
        }

        $payment = $this->paymentService->cancelPayment($payment);

        return response()->json([
            'data' => $payment,
            'message' => 'AP Payment cancelled successfully',
        ]);
    }

    /**
     * Auto-allocate payment to invoices.
     */
    public function autoAllocate(ApPayment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        if (!$payment->canBeUpdated()) {
            return response()->json([
                'message' => 'Cannot allocate to a posted payment',
            ], 422);
        }

        $allocations = $this->paymentService->autoAllocate($payment);

        return response()->json([
            'data' => [
                'payment' => $payment->fresh(['supplier', 'allocations.invoice']),
                'allocations' => $allocations,
            ],
            'message' => 'Payment auto-allocated successfully',
        ]);
    }

    /**
     * Get supplier payment summary.
     */
    public function supplierSummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApPayment::class);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $supplierId = (int) $request->input('supplier_id');
        $summary = $this->paymentService->getSupplierPaymentSummary($supplierId);

        return response()->json([
            'data' => $summary,
        ]);
    }
}
