<?php

declare(strict_types=1);

namespace App\Docs\Examples;

use OpenApi\Attributes as OA;

/**
 * Sales Order API Examples
 */
class OrderExamples
{
    #[OA\Schema(
        schema: 'OrderDeliverSuccess',
        title: 'Order Deliver Success Example',
        example: [
            'id' => 1,
            'customer_id' => 5,
            'status' => 'delivered',
            'subtotal' => 10000.00,
            'discount' => 500.00,
            'tax' => 665.00,
            'total' => 10165.00,
            'delivered_at' => '2025-01-15T16:30:00Z',
            'items' => [
                [
                    'id' => 1,
                    'product_id' => 10,
                    'qty' => 2.000,
                    'price' => 5000.00,
                    'discount' => 0.00,
                    'total' => 10000.00,
                ],
            ],
        ]
    )]
    public function deliverSuccess(): void {}

    #[OA\Schema(
        schema: 'OrderPaySuccess',
        title: 'Order Pay Success Example',
        example: [
            'order' => [
                'id' => 1,
                'customer_id' => 5,
                'status' => 'paid',
                'total' => 10165.00,
                'paid_amount' => 10165.00,
            ],
            'payment' => [
                'id' => 1,
                'order_id' => 1,
                'amount' => 10165.00,
                'method' => 'transfer',
                'paid_at' => '2025-01-15T10:00:00Z',
                'ref_no' => 'TXN123456',
            ],
        ]
    )]
    public function paySuccess(): void {}

    #[OA\Schema(
        schema: 'OrderDeliverError',
        title: 'Order Deliver Error - Insufficient Stock',
        example: [
            'message' => 'Insufficient stock for product: Modern Sofa. Available: 1, Required: 2',
            'code' => 'INSUFFICIENT_STOCK',
        ]
    )]
    public function deliverError(): void {}
}
