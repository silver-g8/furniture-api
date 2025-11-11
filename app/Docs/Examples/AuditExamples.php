<?php

declare(strict_types=1);

namespace App\Docs\Examples;

use OpenApi\Attributes as OA;

/**
 * Audit Log API Examples
 */
class AuditExamples
{
    #[OA\Schema(
        schema: 'AuditLogListWithFilters',
        title: 'Audit Log List with Filters Example',
        example: [
            'data' => [
                [
                    'id' => 1,
                    'auditable_type' => 'App\\Models\\SalesReturn',
                    'auditable_id' => 5,
                    'action' => 'status_change',
                    'user_id' => 3,
                    'old_values' => ['status' => 'draft'],
                    'new_values' => ['status' => 'approved'],
                    'metadata' => ['ip_address' => '192.168.1.100'],
                    'created_at' => '2025-01-15T14:20:00Z',
                ],
                [
                    'id' => 2,
                    'auditable_type' => 'App\\Models\\Purchase',
                    'auditable_id' => 10,
                    'action' => 'create',
                    'user_id' => 1,
                    'old_values' => null,
                    'new_values' => ['supplier_id' => 2, 'status' => 'draft'],
                    'metadata' => ['ip_address' => '192.168.1.101'],
                    'created_at' => '2025-01-15T09:00:00Z',
                ],
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 5,
                'per_page' => 15,
                'to' => 15,
                'total' => 73,
            ],
        ]
    )]
    public function listWithFilters(): void {}

    #[OA\Schema(
        schema: 'AuditLogFilterByType',
        title: 'Audit Log Filter by Document Type Example',
        example: [
            'data' => [
                [
                    'id' => 1,
                    'auditable_type' => 'App\\Models\\SalesReturn',
                    'auditable_id' => 5,
                    'action' => 'status_change',
                    'created_at' => '2025-01-15T14:20:00Z',
                ],
                [
                    'id' => 3,
                    'auditable_type' => 'App\\Models\\SalesReturn',
                    'auditable_id' => 6,
                    'action' => 'create',
                    'created_at' => '2025-01-14T10:15:00Z',
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
    public function filterByType(): void {}
}
