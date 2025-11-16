<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductStoreRequest;
use App\Http\Requests\Catalog\ProductUpdateRequest;
use App\Http\Resources\ProductMetaResource;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
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

        $query = Product::with(['category', 'brand'])
            ->withSum('stocks', 'quantity');

        $search = isset($validated['search']) ? trim((string) $validated['search']) : null;
        if ($search !== null && $search !== '') {
            $likeSearch = '%'.$search.'%';
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

        // Calculate on_hand from stocks for each product (using withSum to avoid N+1)
        $products->getCollection()->transform(function ($product) {
            $product->on_hand = (int) ($product->stocks_sum_quantity ?? 0);
            return $product;
        });

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
                required: ['sku', 'name', 'category_id', 'status', 'on_hand'],
                properties: [
                    new OA\Property(property: 'sku', type: 'string', example: 'SOFA-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Modern Sofa'),
                    new OA\Property(property: 'description', type: 'string', example: 'Comfortable modern sofa', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active', 'inactive', 'archived'], example: 'draft'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 0.0, nullable: true, minimum: 0, description: 'Price not editable in form, defaults to 0'),
                    new OA\Property(property: 'cost', type: 'number', format: 'float', example: 8900.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'brand_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'image_url', type: 'string', example: 'https://cdn.example.com/products/sofa.jpg', nullable: true),
                    new OA\Property(property: 'on_hand', type: 'integer', example: 10, description: 'Current stock on hand'),
                ],
            ),
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

        return response()->json($this->formatProduct($product), Response::HTTP_CREATED);
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

        return response()->json($this->formatProduct($product));
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
                    new OA\Property(property: 'sku', type: 'string', example: 'SOFA-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Modern Sofa Deluxe'),
                    new OA\Property(property: 'description', type: 'string', example: 'Updated description', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active', 'inactive', 'archived'], example: 'active'),
                    new OA\Property(property: 'price_tagged', type: 'number', format: 'float', example: 15000.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'price_discounted_tag', type: 'number', format: 'float', example: 14500.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'price_discounted_net', type: 'number', format: 'float', example: 14000.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'price_vat', type: 'number', format: 'float', example: 15270.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'price_vat_credit', type: 'number', format: 'float', example: 15270.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'cost', type: 'number', format: 'float', example: 9200.0, nullable: true, minimum: 0),
                    new OA\Property(property: 'category_id', type: 'integer', example: 2),
                    new OA\Property(property: 'brand_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'image_url', type: 'string', example: 'https://cdn.example.com/products/sofa-updated.jpg', nullable: true),
                    new OA\Property(property: 'on_hand', type: 'integer', example: 5, description: 'Current stock on hand'),
                ],
            ),
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

        return response()->json($this->formatProduct($product));
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

    #[OA\Get(
        path: '/api/v1/products/meta',
        summary: 'Get product UI metadata',
        description: 'Returns field configuration for index, create/edit form, and detail views',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product UI metadata',
                content: new OA\JsonContent(ref: '#/components/schemas/ProductMeta')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function meta(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $meta = [
            'index_fields' => [
                'sku',
                'name',
                'category_name',
                'brand_name',
                // use price_tagged as the main price column
                'price_tagged',
                'on_hand',
                'status',
                'actions',
            ],
            'form_fields' => [
                [
                    'key' => 'sku',
                    'label' => 'catalog.products.fields.sku',
                    'component' => 'q-input',
                    'rules' => ['required'],
                    'props' => [
                        'type' => 'text',
                    ],
                ],
                [
                    'key' => 'name',
                    'label' => 'catalog.products.fields.name',
                    'component' => 'q-input',
                    'rules' => ['required'],
                    'props' => [
                        'type' => 'text',
                    ],
                ],
                [
                    'key' => 'description',
                    'label' => 'catalog.products.fields.description',
                    'component' => 'q-input',
                    'rules' => [],
                    'props' => [
                        'type' => 'textarea',
                    ],
                ],
                [
                    'key' => 'status',
                    'label' => 'catalog.products.fields.status',
                    'component' => 'q-select',
                    'rules' => ['required'],
                    'props' => [
                        'option_source' => 'statuses',
                    ],
                ],
                [
                    'key' => 'price_tagged',
                    'label' => 'catalog.products.fields.priceTagged',
                    'component' => 'q-input',
                    'rules' => ['numeric'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                    ],
                ],
                [
                    'key' => 'price_discounted_tag',
                    'label' => 'catalog.products.fields.priceDiscountedTag',
                    'component' => 'q-input',
                    'rules' => ['numeric'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                    ],
                ],
                [
                    'key' => 'price_discounted_net',
                    'label' => 'catalog.products.fields.priceDiscountedNet',
                    'component' => 'q-input',
                    'rules' => ['numeric'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                    ],
                ],
                [
                    'key' => 'price_vat',
                    'label' => 'catalog.products.fields.priceVat',
                    'component' => 'q-input',
                    'rules' => ['numeric'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                    ],
                ],
                [
                    'key' => 'price_vat_credit',
                    'label' => 'catalog.products.fields.priceVatCredit',
                    'component' => 'q-input',
                    'rules' => ['numeric'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                    ],
                ],
                [
                    'key' => 'on_hand',
                    'label' => 'catalog.products.fields.on_hand',
                    'component' => 'q-input',
                    'rules' => ['required', 'integer'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => 1,
                    ],
                ],
                [
                    'key' => 'cost',
                    'label' => 'catalog.products.fields.cost',
                    'component' => 'q-input',
                    'rules' => ['numeric'],
                    'props' => [
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                    ],
                ],
                [
                    'key' => 'category_id',
                    'label' => 'catalog.products.fields.category',
                    'component' => 'q-select',
                    'rules' => ['required'],
                    'props' => [
                        'option_source' => 'categories',
                        'option_value' => 'id',
                        'option_label' => 'name',
                    ],
                ],
                [
                    'key' => 'brand_id',
                    'label' => 'catalog.products.fields.brand',
                    'component' => 'q-select',
                    'rules' => [],
                    'props' => [
                        'option_source' => 'brands',
                        'option_value' => 'id',
                        'option_label' => 'name',
                    ],
                ],
                [
                    'key' => 'imageUrl',
                    'label' => 'catalog.products.fields.imageUrl',
                    'component' => 'q-input',
                    'rules' => [],
                    'props' => [
                        'type' => 'text',
                    ],
                ],
            ],
            'show_fields' => [
                'sku',
                'status',
                'category_name',
                'brand_name',
                // display tagged price as the primary price
                'price_tagged',
                'on_hand',
                'description',
            ],
        ];

        return response()->json((new ProductMetaResource($meta))->toArray(request()));
    }

    #[OA\Post(
        path: '/api/v1/products/{id}/image',
        summary: 'Upload product image',
        description: 'Upload an image file for a product and store it in public storage',
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
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(
                            property: 'image',
                            type: 'string',
                            format: 'binary',
                            description: 'Image file (jpg, jpeg, png, webp, max 2MB)'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Image uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'image_url', type: 'string', example: '/storage/products/1/abc123.jpg'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $validated['image'];
        $directory = "products/{$product->id}";
        $filename = $file->hashName();

        // Delete old file if exists
        if ($product->image_url) {
            $parsedUrl = parse_url($product->image_url, PHP_URL_PATH);
            if (is_string($parsedUrl)) {
                $oldPath = str_replace('/storage/', '', $parsedUrl);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        // Store new file
        $path = Storage::disk('public')->putFileAs($directory, $file, $filename);

        // Generate public URL (putFileAs returns string on success)
        $imageUrl = is_string($path) ? Storage::disk('public')->url($path) : '';

        // Update product
        $product->update(['image_url' => $imageUrl]);

        return response()->json([
            'image_url' => $imageUrl,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}/image',
        summary: 'Delete product image',
        description: 'Delete the image file for a product and clear the image_url field',
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
            new OA\Response(response: 200, description: 'Image deleted successfully'),
            new OA\Response(response: 204, description: 'Image deleted (no content)'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function deleteImage(Product $product): Response
    {
        $this->authorize('update', $product);

        // Delete physical file if exists
        if ($product->image_url) {
            $parsedUrl = parse_url($product->image_url, PHP_URL_PATH);
            if (is_string($parsedUrl)) {
                $path = str_replace('/storage/', '', $parsedUrl);
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // Clear image_url field
        $product->update(['image_url' => null]);

        return response()->noContent();
    }

    #[OA\Get(
        path: '/api/v1/products/{id}/stock-summary',
        summary: 'Get product stock summary',
        description: 'Get stock summary for a product across all warehouses',
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
                description: 'Product stock summary',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Product not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function stockSummary(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        // Load stocks with warehouse information
        $stocks = $product->stocks()->with('warehouse')->get();

        // Calculate total on hand
        $totalOnHand = $stocks->sum('quantity');

        // Group by warehouse
        $warehouseStocks = $stocks->map(function ($stock) {
            return [
                'stock_id' => $stock->id,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse' => [
                    'id' => $stock->warehouse->id,
                    'code' => $stock->warehouse->code,
                    'name' => $stock->warehouse->name,
                    'is_active' => $stock->warehouse->is_active,
                ],
                'quantity' => $stock->quantity,
                'updated_at' => $stock->updated_at,
            ];
        });

        // Calculate summary statistics
        $warehousesWithStock = $stocks->where('quantity', '>', 0)->count();
        $warehousesZeroStock = $stocks->where('quantity', 0)->count();
        $totalWarehouses = $stocks->count();

        return response()->json([
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                ],
                'total_on_hand' => $totalOnHand,
                'warehouses' => $warehouseStocks,
                'summary' => [
                    'total_warehouses' => $totalWarehouses,
                    'warehouses_with_stock' => $warehousesWithStock,
                    'warehouses_zero_stock' => $warehousesZeroStock,
                ],
            ],
        ]);
    }

    /**
     * Format product payload to allowed fields.
     *
     * @return array<string, mixed>
     */
    private function formatProduct(Product $product): array
    {
        $product->loadMissing(['category', 'brand']);

        $productArray = Arr::only(
            $product->toArray(),
            [
                'id',
                'sku',
                'name',
                'description',
                'status',
                // base price field has been removed; price_tagged is the canonical price
                'price_tagged',
                'price_discounted_tag',
                'price_discounted_net',
                'price_vat',
                'price_vat_credit',
                'cost',
                'category_id',
                'brand_id',
                'on_hand',
                'image_url',
                'created_at',
                'updated_at',
                'brand',
                'category',
            ],
        );

        // Calculate on_hand from stocks (sum of all warehouse quantities)
        // Use withSum if not already loaded to avoid N+1 query
        if (!isset($product->stocks_sum_quantity)) {
            $product->loadSum('stocks', 'quantity');
        }
        $productArray['on_hand'] = (int) ($product->stocks_sum_quantity ?? 0);

        return $productArray;
    }
}
