<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ar\StoreArInvoiceRequest;
use App\Http\Requests\Ar\UpdateArInvoiceRequest;
use App\Http\Resources\ArInvoiceResource;
use App\Models\ArInvoice;
use App\Models\SalesOrder;
use App\Services\Ar\ArInvoiceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class ArInvoiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArInvoiceService $invoiceService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/ar/invoices',
        summary: 'List AR invoices',
        description: 'Get paginated list of AR invoices with filters',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Invoices'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'customer_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'])),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'overdue_only', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', description: 'Search by invoice_no')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Invoices list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ArInvoice::class);

        $validated = Validator::make($request->query(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', Rule::in(['draft', 'issued', 'partially_paid', 'paid', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'overdue_only' => ['nullable', 'string', 'in:true,false,1,0'],
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();

        // Convert overdue_only string to boolean
        if (isset($validated['overdue_only'])) {
            $validated['overdue_only'] = in_array($validated['overdue_only'], ['true', '1'], true);
        }

        $query = ArInvoice::query()->with(['customer', 'salesOrder']);

        if (isset($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['date_from'])) {
            $query->where('invoice_date', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('invoice_date', '<=', $validated['date_to']);
        }

        if (isset($validated['overdue_only']) && $validated['overdue_only']) {
            $query->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->where('open_amount', '>', 0);
        }

        if (isset($validated['search'])) {
            $query->where('invoice_no', 'like', '%' . $validated['search'] . '%');
        }

        $perPage = $validated['per_page'] ?? 15;
        $invoices = $query->orderByDesc('created_at')->paginate($perPage);

        return ArInvoiceResource::collection($invoices);
    }

    #[OA\Post(
        path: '/api/v1/ar/invoices',
        summary: 'Create AR invoice',
        description: 'Create a new AR invoice (manual or from sales order)',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Invoices'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id', 'invoice_date'],
                properties: [
                    new OA\Property(property: 'customer_id', type: 'integer'),
                    new OA\Property(property: 'sales_order_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'invoice_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'subtotal_amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'discount_amount', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'tax_amount', type: 'number', format: 'float', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Invoice created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreArInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', ArInvoice::class);

        $data = $request->validated();

        // If sales_order_id is provided, create from sales order
        if (isset($data['sales_order_id'])) {
            $salesOrder = SalesOrder::findOrFail($data['sales_order_id']);
            $invoice = $this->invoiceService->createFromSalesOrder($salesOrder, $data);
        } else {
            $invoice = $this->invoiceService->createFromPayload($data);
        }

        return response()->json(new ArInvoiceResource($invoice->load(['customer', 'salesOrder'])), Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/v1/ar/invoices/{id}',
        summary: 'Get AR invoice',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Invoices'],
        responses: [
            new OA\Response(response: 200, description: 'Invoice details'),
            new OA\Response(response: 404, description: 'Invoice not found'),
        ]
    )]
    public function show(ArInvoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'salesOrder', 'allocations.invoice', 'allocations.receipt']);

        return response()->json(new ArInvoiceResource($invoice));
    }

    #[OA\Put(
        path: '/api/v1/ar/invoices/{id}',
        summary: 'Update AR invoice',
        description: 'Update invoice (only allowed for draft status)',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Invoices'],
        responses: [
            new OA\Response(response: 200, description: 'Invoice updated'),
            new OA\Response(response: 403, description: 'Invoice cannot be updated'),
        ]
    )]
    public function update(UpdateArInvoiceRequest $request, ArInvoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();

        if (isset($data['subtotal_amount']) || isset($data['discount_amount']) || isset($data['tax_amount'])) {
            if (isset($data['subtotal_amount'])) {
                $invoice->subtotal_amount = $data['subtotal_amount'];
            }
            if (isset($data['discount_amount'])) {
                $invoice->discount_amount = $data['discount_amount'];
            }
            if (isset($data['tax_amount'])) {
                $invoice->tax_amount = $data['tax_amount'];
            }
            $invoice->calculateTotal();
            $invoice->recalculateOpenAmount();
        }

        $invoice->fill($data);
        $invoice->save();

        return response()->json(new ArInvoiceResource($invoice->load(['customer', 'salesOrder'])));
    }

    #[OA\Post(
        path: '/api/v1/ar/invoices/{id}/issue',
        summary: 'Issue AR invoice',
        description: 'Change invoice status from draft to issued',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Invoices'],
        responses: [
            new OA\Response(response: 200, description: 'Invoice issued'),
            new OA\Response(response: 403, description: 'Invoice cannot be issued'),
        ]
    )]
    public function issue(ArInvoice $invoice): JsonResponse
    {
        $this->authorize('issue', $invoice);

        $invoice = $this->invoiceService->issue($invoice);

        return response()->json(new ArInvoiceResource($invoice->load(['customer', 'salesOrder'])));
    }

    #[OA\Post(
        path: '/api/v1/ar/invoices/{id}/cancel',
        summary: 'Cancel AR invoice',
        description: 'Cancel invoice (only if no payments allocated)',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Invoices'],
        responses: [
            new OA\Response(response: 200, description: 'Invoice cancelled'),
            new OA\Response(response: 403, description: 'Invoice cannot be cancelled'),
        ]
    )]
    public function cancel(ArInvoice $invoice): JsonResponse
    {
        $this->authorize('cancel', $invoice);

        $invoice = $this->invoiceService->cancel($invoice);

        return response()->json(new ArInvoiceResource($invoice->load(['customer', 'salesOrder'])));
    }
}

