<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\CustomerStoreRequest;
use App\Http\Requests\Sales\CustomerUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\Ar\CustomerArSummaryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerArSummaryService $arSummaryService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->search($request->string('search')->toString())
            ->paymentType($request->string('payment_type')->toString() ?: null)
            ->customerGroup($request->string('customer_group')->toString() ?: null)
            ->hasOutstanding(
                $request->has('has_outstanding')
                    ? $request->boolean('has_outstanding')
                    : null
            )
            ->overCreditLimit(
                $request->has('over_credit_limit')
                    ? $request->boolean('over_credit_limit')
                    : null
            );

        // รองรับ is_active และ group สำหรับ backward compatibility
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('group')) {
            $query->group($request->string('group')->toString());
        }

        // การจัดเรียง (default: name asc)
        $sort = $request->string('sort')->toString();

        $query = match ($sort) {
            'outstanding_desc' => $query->orderByDesc('outstanding_balance'),
            'outstanding_asc' => $query->orderBy('outstanding_balance'),
            'name_desc' => $query->orderByDesc('name'),
            'created_desc' => $query->orderByDesc('created_at'),
            'created_asc' => $query->orderBy('created_at'),
            default => $query->orderBy('name'),
        };

        $perPage = (int) $request->input('per_page', 15);

        $customers = $query->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return response()->json($customer, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $customer->load(['orders', 'addresses']);

        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return response()->json($customer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): Response
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->noContent();
    }

    #[OA\Get(
        path: '/api/v1/customers/{customer}/ar-summary',
        summary: 'Get customer AR summary',
        description: 'Get accounts receivable summary for a customer',
        security: [['bearerAuth' => []]],
        tags: ['AR', 'Customers'],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'AR summary',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'customer_id', type: 'integer'),
                        new OA\Property(property: 'total_invoiced', type: 'number', format: 'float'),
                        new OA\Property(property: 'total_paid', type: 'number', format: 'float'),
                        new OA\Property(property: 'total_outstanding', type: 'number', format: 'float'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Customer not found'),
        ]
    )]
    public function arSummary(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $summary = $this->arSummaryService->getSummary($customer->id);

        return response()->json($summary);
    }
}
