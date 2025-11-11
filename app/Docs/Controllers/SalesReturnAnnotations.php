<?php

declare(strict_types=1);

namespace App\Docs\Controllers;

use OpenApi\Attributes as OA;

/**
 * This class contains OpenAPI annotations for Sales Return endpoints
 * These annotations should be copied to the actual SalesReturnController
 */
class SalesReturnAnnotations
{
    #[OA\Get(
        path: '/api/v1/returns/sales',
        summary: 'List sales returns',
        description: 'Get paginated list of sales returns with filters',
        security: [['bearerAuth' => []]],
        tags: ['Returns'],
        parameters: [
            new OA\Parameter(
                name: 'order_id',
                in: 'query',
                description: 'Filter by order ID',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'warehouse_id',
                in: 'query',
                description: 'Filter by warehouse ID',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by status',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['draft', 'approved', 'completed', 'rejected'])
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search in reason and notes',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page (max 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sales returns list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SalesReturn')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/returns/sales',
        summary: 'Create sales return',
        description: 'Create a new sales return from an order',
        security: [['bearerAuth' => []]],
        tags: ['Returns'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'warehouse_id', 'items'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer', example: 1),
                    new OA\Property(property: 'warehouse_id', type: 'integer', example: 1),
                    new OA\Property(property: 'reason', type: 'string', example: 'Defective product', nullable: true),
                    new OA\Property(property: 'notes', type: 'string', example: 'Customer complaint', nullable: true),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['product_id', 'quantity', 'price'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'integer', example: 1),
                                new OA\Property(property: 'quantity', type: 'number', format: 'decimal', example: 1.000),
                                new OA\Property(property: 'price', type: 'number', format: 'decimal', example: 5000.00),
                                new OA\Property(property: 'remark', type: 'string', example: 'Scratched surface', nullable: true),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Sales return created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalesReturn'),
                        new OA\Property(property: 'message', type: 'string', example: 'Sales return created successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 500, description: 'Server error', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function store(): void {}

    #[OA\Post(
        path: '/api/v1/returns/sales/{id}/approve',
        summary: 'Approve sales return',
        description: 'Approve a sales return and update inventory',
        security: [['bearerAuth' => []]],
        tags: ['Returns'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Sales return ID',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sales return approved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalesReturn'),
                        new OA\Property(property: 'message', type: 'string', example: 'Sales return approved successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Cannot approve', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function approve(): void {}
}
