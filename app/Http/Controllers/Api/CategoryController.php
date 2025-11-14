<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CategoryStoreRequest;
use App\Http\Requests\Catalog\CategoryUpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/api/v1/categories',
        summary: 'List categories',
        description: 'Get paginated list of product categories with parent-child relationships. Use tree=1 to get nested tree structure.',
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
                name: 'parent_id',
                in: 'query',
                description: 'Filter by parent category ID',
                required: false,
                schema: new OA\Schema(type: 'integer', nullable: true)
            ),
            new OA\Parameter(
                name: 'is_active',
                in: 'query',
                description: 'Filter by active status',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'tree',
                in: 'query',
                description: 'Return nested tree structure (1) or flat paginated list (0)',
                required: false,
                schema: new OA\Schema(type: 'integer', enum: [0, 1], default: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories list (tree or paginated)',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'slug', type: 'string'),
                                    new OA\Property(property: 'parentId', type: 'integer', nullable: true),
                                    new OA\Property(property: 'isActive', type: 'boolean'),
                                    new OA\Property(property: 'children', type: 'array', items: new OA\Items),
                                ]
                            )
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/Category')
                                ),
                                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();

        // Apply filters
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Check if tree view is requested
        if ($request->boolean('tree')) {
            $categories = $query->with('children')->get();
            $tree = Category::buildTree($categories);

            return response()->json($tree);
        }

        // Paginated flat list
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100); // Clamp between 1 and 100

        $categories = $query->with(['parent', 'children'])->paginate($perPage);

        return CategoryResource::collection($categories)->response();
    }

    #[OA\Post(
        path: '/api/v1/categories',
        summary: 'Create category',
        description: 'Create a new product category',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', example: null, nullable: true),
                    new OA\Property(property: 'name', type: 'string', example: 'Living Room'),
                    new OA\Property(property: 'slug', type: 'string', example: 'living-room', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created',
                content: new OA\JsonContent(ref: '#/components/schemas/Category')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/api/v1/categories/{id}',
        summary: 'Get category',
        description: 'Get a specific category with parent, children, and products',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Category ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category details',
                content: new OA\JsonContent(ref: '#/components/schemas/Category')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Category not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $category->load(['parent', 'children', 'products']);

        return (new CategoryResource($category))->response();
    }

    #[OA\Put(
        path: '/api/v1/categories/{id}',
        summary: 'Update category',
        description: 'Update an existing category',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Category ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', example: null, nullable: true),
                    new OA\Property(property: 'name', type: 'string', example: 'Living Room Furniture'),
                    new OA\Property(property: 'slug', type: 'string', example: 'living-room-furniture'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category updated',
                content: new OA\JsonContent(ref: '#/components/schemas/Category')
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Category not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return (new CategoryResource($category))->response();
    }

    #[OA\Delete(
        path: '/api/v1/categories/{id}',
        summary: 'Delete category',
        description: 'Delete a category. Returns 422 if category has children or products.',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Category ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Category deleted successfully'),
            new OA\Response(
                response: 422,
                description: 'Cannot delete category',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot delete category with child categories.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Category not found', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function destroy(Category $category): Response|JsonResponse
    {
        $this->authorize('delete', $category);

        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with child categories.',
            ], 422);
        }

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category that has products.',
            ], 422);
        }

        $category->delete();

        return response()->noContent();
    }
}
