<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ap\StoreApInvoiceRequest;
use App\Http\Requests\Ap\UpdateApInvoiceRequest;
use App\Models\ApInvoice;
use App\Services\Ap\ApInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApInvoiceController extends Controller
{
    public function __construct(
        protected ApInvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of AP invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApInvoice::class);

        $query = ApInvoice::with(['supplier', 'purchase']);

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        // Filter by purchase
        if ($request->filled('purchase_id')) {
            $query->where('purchase_id', $request->input('purchase_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('invoice_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('invoice_date', '<=', $request->input('to_date'));
        }

        // Filter overdue
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Filter unpaid
        if ($request->boolean('unpaid')) {
            $query->unpaid();
        }

        // Search by invoice number or note
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 15), 100);
        $invoices = $query->latest('invoice_date')->paginate($perPage);

        return response()->json([
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created AP invoice.
     */
    public function store(StoreApInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', ApInvoice::class);

        $invoice = $this->invoiceService->createInvoice($request->validated());

        return response()->json([
            'data' => $invoice,
            'message' => 'AP Invoice created successfully',
        ], 201);
    }

    /**
     * Display the specified AP invoice.
     */
    public function show(ApInvoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['supplier', 'purchase', 'allocations.payment']);

        return response()->json([
            'data' => $invoice,
        ]);
    }

    /**
     * Update the specified AP invoice.
     */
    public function update(UpdateApInvoiceRequest $request, ApInvoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->canBeUpdated()) {
            return response()->json([
                'message' => 'Cannot update an issued or paid invoice',
            ], 422);
        }

        $invoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

        return response()->json([
            'data' => $invoice,
            'message' => 'AP Invoice updated successfully',
        ]);
    }

    /**
     * Issue an AP invoice.
     */
    public function issue(ApInvoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->canBeIssued()) {
            return response()->json([
                'message' => 'Invoice cannot be issued. Ensure it is in draft status and has a valid amount.',
            ], 422);
        }

        $invoice = $this->invoiceService->issueInvoice($invoice);

        return response()->json([
            'data' => $invoice,
            'message' => 'AP Invoice issued successfully',
        ]);
    }

    /**
     * Cancel an AP invoice.
     */
    public function cancel(ApInvoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (!$invoice->canBeCancelled()) {
            return response()->json([
                'message' => 'Invoice cannot be cancelled. It may have payments allocated.',
            ], 422);
        }

        $invoice = $this->invoiceService->cancelInvoice($invoice);

        return response()->json([
            'data' => $invoice,
            'message' => 'AP Invoice cancelled successfully',
        ]);
    }

    /**
     * Get aging report for a supplier.
     */
    public function aging(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApInvoice::class);

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $supplierId = (int) $request->input('supplier_id');
        $aging = $this->invoiceService->getAgingReport($supplierId);

        return response()->json([
            'data' => $aging,
        ]);
    }
}
