<?php

declare(strict_types=1);

namespace App\Docs\Examples;

use OpenApi\Attributes as OA;

/**
 * Sales Return API Examples
 */
class SalesReturnExamples
{
    #[OA\Schema(
        schema: 'SalesReturnCreateSuccess',
        title: 'Sales Return Create Success Example',
        example: [
            'data' => [
                'id' => 1,
                'order_id' => 5,
                'warehouse_id' => 1,
                'reason' => 'Defective product - scratched surface',
                'notes' => 'Customer reported damage upon delivery',
                'status' => 'draft',
                'subtotal' => 5000.00,
                'tax' => 350.00,
                'total' => 5350.00,
                'created_at' => '2025-01-15T10:30:00Z',
                'updated_at' => '2025-01-15T10:30:00Z',
            ],
            'message' => 'Sales return created successfully',
        ]
    )]
    public function createSuccess(): void {}

    #[OA\Schema(
        schema: 'SalesReturnApproveSuccess',
        title: 'Sales Return Approve Success Example',
        example: [
            'data' => [
                'id' => 1,
                'order_id' => 5,
                'warehouse_id' => 1,
                'reason' => 'Defective product - scratched surface',
                'status' => 'approved',
                'subtotal' => 5000.00,
                'tax' => 350.00,
                'total' => 5350.00,
                'approved_at' => '2025-01-15T14:20:00Z',
                'approved_by' => 3,
            ],
            'message' => 'Sales return approved successfully',
        ]
    )]
    public function approveSuccess(): void {}

    #[OA\Schema(
        schema: 'SalesReturnValidationError',
        title: 'Sales Return Validation Error Example',
        example: [
            'message' => 'The given data was invalid.',
            'errors' => [
                'order_id' => ['The order id field is required.'],
                'items' => ['The items field must contain at least 1 item.'],
                'items.0.quantity' => ['The quantity must be greater than 0.'],
            ],
        ]
    )]
    public function validationError(): void {}

    #[OA\Schema(
        schema: 'SalesReturnApproveError',
        title: 'Sales Return Approve Error Example',
        example: [
            'message' => 'Cannot approve sales return. Return must be in draft status.',
            'code' => 'INVALID_STATUS',
        ]
    )]
    public function approveError(): void {}
}
