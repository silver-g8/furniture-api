<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'User entity',
    required: ['id', 'name', 'email', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-01T00:00:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-01-01T00:00:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'Product',
    title: 'Product',
    description: 'Product entity',
    required: ['id', 'sku', 'name', 'type', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'type', type: 'string', enum: ['simple', 'configurable'], example: 'simple'),
        new OA\Property(property: 'sku', type: 'string', example: 'SOFA-001'),
        new OA\Property(property: 'name', type: 'string', example: 'Modern Sofa'),
        new OA\Property(property: 'category_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'brand_id', type: 'integer', format: 'int64', example: 1, nullable: true),
        new OA\Property(property: 'description', type: 'string', example: 'Comfortable modern sofa', nullable: true),
        new OA\Property(property: 'tax_class', type: 'string', example: 'standard'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ProductFormField',
    title: 'Product Form Field Definition',
    description: 'Configuration for a product form field',
    required: ['key', 'label', 'component', 'rules', 'props'],
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'name'),
        new OA\Property(property: 'label', type: 'string', example: 'catalog.products.fields.name'),
        new OA\Property(property: 'component', type: 'string', example: 'q-input'),
        new OA\Property(
            property: 'rules',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['required']
        ),
        new OA\Property(
            property: 'props',
            type: 'object',
            additionalProperties: true,
            example: ['type' => 'text']
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProductMeta',
    title: 'Product UI Metadata',
    description: 'Metadata describing how to render product screens',
    required: ['index_fields', 'form_fields', 'show_fields'],
    properties: [
        new OA\Property(
            property: 'index_fields',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['sku', 'name', 'category_name', 'brand_name', 'price', 'status', 'actions']
        ),
        new OA\Property(
            property: 'form_fields',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProductFormField'),
            example: [
                [
                    'key' => 'name',
                    'label' => 'catalog.products.fields.name',
                    'component' => 'q-input',
                    'rules' => ['required'],
                    'props' => ['type' => 'text'],
                ],
            ]
        ),
        new OA\Property(
            property: 'show_fields',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['sku', 'status', 'category_name', 'brand_name', 'price', 'on_hand', 'description']
        ),
    ]
)]
#[OA\Schema(
    schema: 'Category',
    title: 'Category',
    description: 'Product category',
    required: ['id', 'name', 'slug'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'parent_id', type: 'integer', format: 'int64', example: null, nullable: true),
        new OA\Property(property: 'name', type: 'string', example: 'Living Room'),
        new OA\Property(property: 'slug', type: 'string', example: 'living-room'),
    ]
)]
#[OA\Schema(
    schema: 'Option',
    title: 'Select Option',
    description: 'Lightweight option item for dropdowns',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Living Room'),
    ]
)]
#[OA\Schema(
    schema: 'Warehouse',
    title: 'Warehouse',
    description: 'Warehouse entity',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Main Warehouse'),
        new OA\Property(property: 'address', type: 'string', example: '123 Storage St, Bangkok', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Customer',
    title: 'Customer',
    description: 'Customer entity',
    required: ['id', 'type', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'type', type: 'string', enum: ['individual', 'organization'], example: 'individual'),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Smith'),
        new OA\Property(property: 'tax_id', type: 'string', example: '1234567890123', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com', nullable: true),
        new OA\Property(property: 'phone', type: 'string', example: '+66812345678', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'Order',
    title: 'Sales Order',
    description: 'Sales order entity',
    required: ['id', 'customer_id', 'status', 'subtotal', 'tax', 'total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'customer_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'confirmed', 'paid', 'delivered', 'completed', 'cancelled'], example: 'draft'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', example: 10000.00),
        new OA\Property(property: 'discount', type: 'number', format: 'decimal', example: 500.00),
        new OA\Property(property: 'tax', type: 'number', format: 'decimal', example: 665.00),
        new OA\Property(property: 'total', type: 'number', format: 'decimal', example: 10165.00),
        new OA\Property(property: 'notes', type: 'string', example: 'Customer notes', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'OrderItem',
    title: 'Order Item',
    description: 'Sales order line item',
    required: ['id', 'order_id', 'product_id', 'qty', 'price', 'total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'qty', type: 'number', format: 'decimal', example: 2.000),
        new OA\Property(property: 'price', type: 'number', format: 'decimal', example: 5000.00),
        new OA\Property(property: 'discount', type: 'number', format: 'decimal', example: 0.00),
        new OA\Property(property: 'total', type: 'number', format: 'decimal', example: 10000.00),
    ]
)]
#[OA\Schema(
    schema: 'Payment',
    title: 'Payment',
    description: 'Payment record',
    required: ['id', 'order_id', 'amount', 'method'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'amount', type: 'number', format: 'decimal', example: 10165.00),
        new OA\Property(property: 'method', type: 'string', example: 'transfer'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'ref_no', type: 'string', example: 'TXN123456', nullable: true),
        new OA\Property(property: 'notes', type: 'string', example: 'Payment notes', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'SalesReturn',
    title: 'Sales Return',
    description: 'Sales return entity',
    required: ['id', 'order_id', 'warehouse_id', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'order_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'warehouse_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'reason', type: 'string', example: 'Defective product', nullable: true),
        new OA\Property(property: 'notes', type: 'string', example: 'Customer notes', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'approved', 'completed', 'rejected'], example: 'draft'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', example: 5000.00),
        new OA\Property(property: 'tax', type: 'number', format: 'decimal', example: 350.00),
        new OA\Property(property: 'total', type: 'number', format: 'decimal', example: 5350.00),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PurchaseReturn',
    title: 'Purchase Return',
    description: 'Purchase return entity',
    required: ['id', 'purchase_id', 'warehouse_id', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'purchase_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'warehouse_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'reason', type: 'string', example: 'Wrong item received', nullable: true),
        new OA\Property(property: 'notes', type: 'string', example: 'Supplier notes', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'approved', 'completed', 'rejected'], example: 'draft'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', example: 3000.00),
        new OA\Property(property: 'tax', type: 'number', format: 'decimal', example: 210.00),
        new OA\Property(property: 'total', type: 'number', format: 'decimal', example: 3210.00),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AuditLog',
    title: 'Audit Log',
    description: 'Audit log entry',
    required: ['id', 'document_type', 'document_id', 'event', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'document_type', type: 'string', example: 'sales_return'),
        new OA\Property(property: 'document_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'event', type: 'string', enum: ['create', 'update', 'delete', 'status_change'], example: 'create'),
        new OA\Property(property: 'actor_id', type: 'integer', format: 'int64', example: 1, nullable: true),
        new OA\Property(property: 'summary', type: 'string', example: 'Sales return created', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'GoodsReceipt',
    title: 'Goods Receipt Note (GRN)',
    description: 'Goods receipt note entity',
    required: ['id', 'purchase_id', 'received_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'purchase_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'received_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'notes', type: 'string', example: 'Received in good condition', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'InstallationOrder',
    title: 'Installation Order',
    description: 'Installation order entity',
    required: ['id', 'sales_order_id', 'customer_id', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'sales_order_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'customer_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'installation_address_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'scheduled', 'in_progress', 'completed', 'no_show', 'pending_parts', 'cancelled'], example: 'scheduled'),
        new OA\Property(property: 'notes', type: 'string', example: 'Installation notes', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Supplier',
    title: 'Supplier',
    description: 'Supplier entity',
    required: ['id', 'name', 'code'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'SUP001'),
        new OA\Property(property: 'name', type: 'string', example: 'ABC Furniture Supplies'),
        new OA\Property(property: 'contact_name', type: 'string', example: 'John Smith', nullable: true),
        new OA\Property(property: 'contact_email', type: 'string', format: 'email', example: 'john@abcsupplies.com', nullable: true),
        new OA\Property(property: 'contact_phone', type: 'string', example: '+66812345678', nullable: true),
        new OA\Property(property: 'address', type: 'string', example: '123 Supply St, Bangkok', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Purchase',
    title: 'Purchase Order',
    description: 'Purchase order entity',
    required: ['id', 'supplier_id', 'status', 'subtotal', 'tax', 'total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'supplier_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'approved', 'received', 'cancelled'], example: 'draft'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', example: 50000.00),
        new OA\Property(property: 'tax', type: 'number', format: 'decimal', example: 3500.00),
        new OA\Property(property: 'total', type: 'number', format: 'decimal', example: 53500.00),
        new OA\Property(property: 'notes', type: 'string', example: 'Purchase notes', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Stock',
    title: 'Stock',
    description: 'Stock inventory entity',
    required: ['id', 'warehouse_id', 'product_id', 'quantity'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'warehouse_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'quantity', type: 'number', format: 'decimal', example: 100.000),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class EntitySchemas
{
    // This class exists solely to hold OpenAPI schema annotations
}
