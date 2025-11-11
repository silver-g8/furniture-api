<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditLogIndexRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

/**
 * AuditLogController
 *
 * API endpoints for viewing audit logs.
 * Audit logs are read-only and immutable.
 */
class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()
            ->with(['user:id,name,email', 'auditable'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        $filters = $request->filters();

        // Handle type alias translation
        $auditableType = $filters['auditable_type'] ?? null;
        if (isset($filters['type']) && ! $auditableType) {
            $typeAliases = config('audit.type_aliases', []);
            $auditableType = $typeAliases[$filters['type']] ?? null;
        }

        if ($auditableType) {
            $query->forType($auditableType);
        }

        if (isset($filters['auditable_id'])) {
            $query->forDocument((int) $filters['auditable_id']);
        }

        if (isset($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (isset($filters['user_id'])) {
            $query->byUser((int) $filters['user_id']);
        }

        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $query->dateRange(
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null
            );
        }

        // Paginate
        $pagination = $request->pagination();
        $auditLogs = $query->paginate(
            $pagination['per_page'],
            ['*'],
            'page',
            $pagination['page']
        );

        return response()->json([
            'data' => $auditLogs->items(),
            'meta' => [
                'current_page' => $auditLogs->currentPage(),
                'from' => $auditLogs->firstItem(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $auditLogs->perPage(),
                'to' => $auditLogs->lastItem(),
                'total' => $auditLogs->total(),
            ],
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        $auditLog->load(['user:id,name,email', 'auditable']);

        return response()->json([
            'data' => $auditLog,
        ]);
    }
}
