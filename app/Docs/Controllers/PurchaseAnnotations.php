<?php

declare(strict_types=1);

namespace App\Docs\Controllers;

use OpenApi\Attributes as OA;

/**
 * Purchase Controller OpenAPI Annotations
 * Copy these to PurchaseController.php
 */
class PurchaseAnnotations
{
    #[OA\Get(
        path: '/api/v1/purchases',
        summary: 'List purchases',
        description: 'Get paginated list of purchase orders with filters',
        security: [['bearerAuth' => []]],
        tags: ['Procurement'],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'query', description: 'Filter by supplier', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'approved', 'received', 'cancelled'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search in notes', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Purchases list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Purchase')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function index(): void {}

    #[OA\Post(
        path: '/api/v1/purchases/{id}/approve',
        summary: 'Approve purchase',
        description: 'Approve a purchase order',
        security: [['bearerAuth' => []]],
        tags: ['Procurement'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Purchase approved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Purchase'),
                        new OA\Property(property: 'message', type: 'string', example: 'Purchase approved successfully'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Cannot approve', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ]
    )]
    public function approve(): void {}
}
