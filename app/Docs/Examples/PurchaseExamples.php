<?php

declare(strict_types=1);

namespace App\Docs\Examples;

use OpenApi\Attributes as OA;

/**
 * Purchase Order API Examples
 */
class PurchaseExamples
{
    #[OA\Schema(
        schema: 'PurchaseApproveSuccess',
        title: 'Purchase Approve Success Example',
        example: [
            'data' => [
                'id' => 1,
                'supplier_id' => 2,
                'status' => 'approved',
                'subtotal' => 50000.00,
                'tax' => 3500.00,
                'total' => 53500.00,
                'approved_at' => '2025-01-15T09:00:00Z',
                'approved_by' => 1,
                'items' => [
                    [
                        'id' => 1,
                        'product_id' => 10,
                        'quantity' => 50.000,
                        'price' => 1000.00,
                        'total' => 50000.00,
                    ],
                ],
            ],
            'message' => 'Purchase approved successfully',
        ]
    )]
    public function approveSuccess(): void {}

    #[OA\Schema(
        schema: 'PurchaseApproveError',
        title: 'Purchase Approve Error Example',
        example: [
            'message' => 'Purchase cannot be approved. Ensure it has items and is in draft status.',
            'code' => 'CANNOT_APPROVE',
        ]
    )]
    public function approveError(): void {}

    #[OA\Schema(
        schema: 'PurchaseListWithFilters',
        title: 'Purchase List with Filters Example',
        example: [
            'data' => [
                [
                    'id' => 1,
                    'supplier_id' => 2,
                    'status' => 'approved',
                    'total' => 53500.00,
                    'created_at' => '2025-01-15T09:00:00Z',
                ],
                [
                    'id' => 2,
                    'supplier_id' => 2,
                    'status' => 'draft',
                    'total' => 25000.00,
                    'created_at' => '2025-01-14T14:30:00Z',
                ],
            ],
            'meta' => [
                'current_page' => 1,
                'per_page' => 15,
                'total' => 2,
                'last_page' => 1,
            ],
        ]
    )]
    public function listWithFilters(): void {}
}
