<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ar\StoreArReceiptRequest;
use App\Http\Requests\Ar\UpdateArReceiptRequest;
use App\Http\Resources\ArReceiptResource;
use App\Models\ArReceipt;
use App\Services\Ar\ArReceiptService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class ArReceiptController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ArReceiptService $receiptService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/ar/receipts',
        summary: 'List AR receipts',
        description: 'Get paginated list of AR receipts with filters',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Receipts'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'customer_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'posted', 'cancelled'])),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string', description: 'Search by receipt_no')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Receipts list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ArReceipt::class);

        $validated = Validator::make($request->query(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', Rule::in(['draft', 'posted', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();

        $query = ArReceipt::query()->with(['customer']);

        if (isset($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['date_from'])) {
            $query->where('receipt_date', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('receipt_date', '<=', $validated['date_to']);
        }

        if (isset($validated['search'])) {
            $query->where('receipt_no', 'like', '%' . $validated['search'] . '%');
        }

        $perPage = $validated['per_page'] ?? 15;
        $receipts = $query->orderByDesc('created_at')->paginate($perPage);

        return ArReceiptResource::collection($receipts);
    }

    #[OA\Post(
        path: '/api/v1/ar/receipts',
        summary: 'Create AR receipt',
        description: 'Create a new AR receipt with allocations',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Receipts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id', 'receipt_date', 'total_amount', 'allocations'],
                properties: [
                    new OA\Property(property: 'customer_id', type: 'integer'),
                    new OA\Property(property: 'receipt_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'transfer', 'credit_card', 'cheque', 'other'], nullable: true),
                    new OA\Property(property: 'reference_no', type: 'string', nullable: true),
                    new OA\Property(property: 'note', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'allocations',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'invoice_id', type: 'integer'),
                                new OA\Property(property: 'allocated_amount', type: 'number', format: 'float'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Receipt created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreArReceiptRequest $request): JsonResponse
    {
        $this->authorize('create', ArReceipt::class);

        $receipt = $this->receiptService->createWithAllocations($request->validated());

        return response()->json(new ArReceiptResource($receipt->load(['customer', 'allocations.invoice'])), 201);
    }

    #[OA\Get(
        path: '/api/v1/ar/receipts/{id}',
        summary: 'Get AR receipt',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Receipts'],
        responses: [
            new OA\Response(response: 200, description: 'Receipt details'),
            new OA\Response(response: 404, description: 'Receipt not found'),
        ]
    )]
    public function show(ArReceipt $receipt): JsonResponse
    {
        $this->authorize('view', $receipt);

        $receipt->load(['customer', 'allocations.invoice', 'allocations.receipt']);

        return response()->json(new ArReceiptResource($receipt));
    }

    #[OA\Put(
        path: '/api/v1/ar/receipts/{id}',
        summary: 'Update AR receipt',
        description: 'Update receipt (only allowed for draft status)',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Receipts'],
        responses: [
            new OA\Response(response: 200, description: 'Receipt updated'),
            new OA\Response(response: 403, description: 'Receipt cannot be updated'),
        ]
    )]
    public function update(UpdateArReceiptRequest $request, ArReceipt $receipt): JsonResponse
    {
        $this->authorize('update', $receipt);

        $data = $request->validated();
        $receipt->fill($data);
        $receipt->save();

        return response()->json(new ArReceiptResource($receipt->load(['customer', 'allocations'])));
    }

    #[OA\Post(
        path: '/api/v1/ar/receipts/{id}/post',
        summary: 'Post AR receipt',
        description: 'Post receipt to apply allocations to invoices',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Receipts'],
        responses: [
            new OA\Response(response: 200, description: 'Receipt posted'),
            new OA\Response(response: 403, description: 'Receipt cannot be posted'),
        ]
    )]
    public function post(ArReceipt $receipt): JsonResponse
    {
        $this->authorize('post', $receipt);

        $receipt = $this->receiptService->post($receipt);

        return response()->json(new ArReceiptResource($receipt->load(['customer', 'allocations.invoice'])));
    }

    #[OA\Post(
        path: '/api/v1/ar/receipts/{id}/cancel',
        summary: 'Cancel AR receipt',
        description: 'Cancel receipt and rollback allocations',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'AR Receipts'],
        responses: [
            new OA\Response(response: 200, description: 'Receipt cancelled'),
            new OA\Response(response: 403, description: 'Receipt cannot be cancelled'),
        ]
    )]
    public function cancel(ArReceipt $receipt): JsonResponse
    {
        $this->authorize('cancel', $receipt);

        $receipt = $this->receiptService->cancel($receipt);

        return response()->json(new ArReceiptResource($receipt->load(['customer', 'allocations.invoice'])));
    }
}

