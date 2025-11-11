<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductStoreRequest;
use App\Http\Requests\Catalog\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/api/v1/products',
        summary: 'List products',
        description: 'Get paginated list of products with category information',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            ),
            new OA\Parameter(
                name: 'brand_id',
                in: 'query',
                description: 'Filter products by brand',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $perPage = $request->input('per_page', 15);
        $perPage = min(max((int) $perPage, 1), 100); // Limit between 1-100

        $allowedSorts = [
            'sku' => 'sku',
            'name' => 'name',
            'price' => 'price',
            'status' => 'status',
            'created_at' => 'created_at',
        ];

        $validated = Validator::make($request->query(), [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'sort' => ['nullable', Rule::in(array_keys($allowedSorts))],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
        ])->validate();

        $query = Product::with(['category', 'brand']);

        $search = isset($validated['search']) ? trim((string) $validated['search']) : null;
        if ($search !== null && $search !== '') {
            $likeSearch = '%' . $search . '%';
            $query->where(static function ($builder) use ($likeSearch): void {
                $builder
                    ->where('name', 'like', $likeSearch)
                    ->orWhere('sku', 'like', $likeSearch);
            });
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (isset($validated['brand_id'])) {
            $query->where('brand_id', $validated['brand_id']);
        }

        if (isset($validated['sort'])) {
            $direction = $validated['order'] ?? 'asc';
            $query->orderBy($allowedSorts[$validated['sort']], $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        $products = $query->paginate($perPage)->appends($validated);

        return response()->json($products);
    }

    #[OA\Post(
        path: '/api/v1/products',
        summary: 'Create product',
        description: 'Create a new product',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'sku', 'name', 'category_id', 'status'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['simple', 'configurable'], example: 'simple'),
                    new OA\Property(property: 'sku', type: 'string', example: 'SOFA-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Modern Sofa'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'brand_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'description', type: 'string', example: 'Comfortable modern sofa', nullable: true),
                    new OA\Property(property: 'tax_class', type: 'string', example: 'standard'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(ProductStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());
        $product->load(['category', 'brand']);

        return response()->json($product, Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        summary: 'Get product',
        description: 'Get a specific product with category information',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Product ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product details',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['category', 'brand']);

        return response()->json($product);
    }

    #[OA\Put(
        path: '/api/v1/products/{id}',
        summary: 'Update product',
        description: 'Update an existing product',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Product ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['simple', 'configurable'], example: 'simple'),
                    new OA\Property(property: 'sku', type: 'string', example: 'SOFA-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Modern Sofa Deluxe'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated description', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated',
                content: new OA\JsonContent(ref: '#/components/schemas/Product')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());
        $product->load(['category', 'brand']);

        return response()->json($product);
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}',
        summary: 'Delete product',
        description: 'Delete a product',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Product ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Product deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }
}
