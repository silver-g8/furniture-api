<?php

declare(strict_types=1);

namespace App\Docs\Controllers;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApInvoice',
    required: ['supplier_id', 'invoice_no', 'invoice_date', 'subtotal_amount', 'grand_total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'supplier_id', type: 'integer', example: 1),
        new OA\Property(property: 'purchase_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'invoice_no', type: 'string', example: 'API-20251116-0001'),
        new OA\Property(property: 'invoice_date', type: 'string', format: 'date', example: '2025-11-16'),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2025-12-16'),
        new OA\Property(property: 'subtotal_amount', type: 'number', format: 'decimal', example: 10000.00),
        new OA\Property(property: 'discount_amount', type: 'number', format: 'decimal', example: 500.00),
        new OA\Property(property: 'tax_amount', type: 'number', format: 'decimal', example: 665.00),
        new OA\Property(property: 'grand_total', type: 'number', format: 'decimal', example: 10165.00),
        new OA\Property(property: 'paid_total', type: 'number', format: 'decimal', example: 5000.00),
        new OA\Property(property: 'open_amount', type: 'number', format: 'decimal', example: 5165.00),
        new OA\Property(property: 'currency', type: 'string', example: 'THB'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'], example: 'issued'),
        new OA\Property(property: 'reference_type', type: 'string', nullable: true, example: 'purchase'),
        new OA\Property(property: 'reference_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Payment for office supplies'),
        new OA\Property(property: 'issued_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ApPayment',
    required: ['supplier_id', 'payment_no', 'payment_date', 'total_amount'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'supplier_id', type: 'integer', example: 1),
        new OA\Property(property: 'payment_no', type: 'string', example: 'APP-20251116-0001'),
        new OA\Property(property: 'payment_date', type: 'string', format: 'date', example: '2025-11-16'),
        new OA\Property(property: 'total_amount', type: 'number', format: 'decimal', example: 15000.00),
        new OA\Property(property: 'payment_method', type: 'string', nullable: true, example: 'transfer'),
        new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'Bank Transfer'),
        new OA\Property(property: 'reference_no', type: 'string', nullable: true, example: 'TRF-1234567890'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'posted', 'cancelled'], example: 'posted'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Payment for invoices'),
        new OA\Property(property: 'posted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ApPaymentAllocation',
    required: ['payment_id', 'invoice_id', 'allocated_amount'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'payment_id', type: 'integer', example: 1),
        new OA\Property(property: 'invoice_id', type: 'integer', example: 1),
        new OA\Property(property: 'allocated_amount', type: 'number', format: 'decimal', example: 5000.00),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'StoreApInvoiceRequest',
    required: ['supplier_id', 'invoice_no', 'invoice_date', 'subtotal_amount'],
    properties: [
        new OA\Property(property: 'supplier_id', type: 'integer', example: 1),
        new OA\Property(property: 'purchase_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'invoice_no', type: 'string', example: 'API-20251116-0001'),
        new OA\Property(property: 'invoice_date', type: 'string', format: 'date', example: '2025-11-16'),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2025-12-16'),
        new OA\Property(property: 'subtotal_amount', type: 'number', format: 'decimal', example: 10000.00),
        new OA\Property(property: 'discount_amount', type: 'number', format: 'decimal', nullable: true, example: 500.00),
        new OA\Property(property: 'tax_amount', type: 'number', format: 'decimal', nullable: true, example: 665.00),
        new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'THB'),
        new OA\Property(property: 'reference_type', type: 'string', nullable: true, example: 'purchase'),
        new OA\Property(property: 'reference_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Payment for office supplies'),
    ]
)]
#[OA\Schema(
    schema: 'StoreApPaymentRequest',
    required: ['supplier_id', 'payment_date', 'total_amount'],
    properties: [
        new OA\Property(property: 'supplier_id', type: 'integer', example: 1),
        new OA\Property(property: 'payment_no', type: 'string', nullable: true, example: 'APP-20251116-0001'),
        new OA\Property(property: 'payment_date', type: 'string', format: 'date', example: '2025-11-16'),
        new OA\Property(property: 'total_amount', type: 'number', format: 'decimal', example: 15000.00),
        new OA\Property(property: 'payment_method', type: 'string', nullable: true, example: 'transfer'),
        new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'Bank Transfer'),
        new OA\Property(property: 'reference_no', type: 'string', nullable: true, example: 'TRF-1234567890'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Payment for invoices'),
        new OA\Property(
            property: 'allocations',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                required: ['invoice_id', 'allocated_amount'],
                properties: [
                    new OA\Property(property: 'invoice_id', type: 'integer', example: 1),
                    new OA\Property(property: 'allocated_amount', type: 'number', format: 'decimal', example: 5000.00),
                ]
            ),
            nullable: true
        ),
    ]
)]
class ApAnnotations
{
    // This class is used only for OpenAPI documentation
}
