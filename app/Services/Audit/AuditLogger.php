<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Jobs\WriteAuditLog;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * AuditLogger Service
 *
 * Central service for logging document-level audit events.
 * Captures who, when, what changed with before/after snapshots.
 */
class AuditLogger
{
    /**
     * Log an audit event for a document.
     *
     * @param  Model  $auditable  The document being audited
     * @param  string  $action  The action performed (created, updated, approved, cancelled, deleted)
     * @param  array<string, mixed>|null  $before  Snapshot before the change
     * @param  array<string, mixed>|null  $after  Snapshot after the change
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    public function log(
        Model $auditable,
        string $action,
        ?array $before = null,
        ?array $after = null,
        array $meta = []
    ): void {
        $request = request();

        $payload = [
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'meta' => $meta ? json_encode($meta) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::afterCommit(function () use ($payload) {
            if (config('audit.queue')) {
                WriteAuditLog::dispatch($payload)->afterCommit();
            } else {
                AuditLog::query()->insert([$payload]);
            }
        });
    }

    /**
     * Log a document creation event.
     *
     * @param  array<string, mixed>  $meta
     */
    public function created(Model $auditable, array $meta = []): void
    {
        $this->log(
            $auditable,
            'created',
            null,
            $this->extractSnapshot($auditable),
            $meta
        );
    }

    /**
     * Log a document update event.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function updated(
        Model $auditable,
        array $before,
        ?array $after = null,
        array $meta = []
    ): void {
        $this->log(
            $auditable,
            'updated',
            $before,
            $after ?? $this->extractSnapshot($auditable),
            $meta
        );
    }

    /**
     * Log a document approval event.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function approved(
        Model $auditable,
        array $before,
        ?array $after = null,
        array $meta = []
    ): void {
        $this->log(
            $auditable,
            'approved',
            $before,
            $after ?? $this->extractSnapshot($auditable),
            $meta
        );
    }

    /**
     * Log a document cancellation event.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function cancelled(
        Model $auditable,
        array $before,
        ?array $after = null,
        array $meta = []
    ): void {
        $this->log(
            $auditable,
            'cancelled',
            $before,
            $after ?? $this->extractSnapshot($auditable),
            $meta
        );
    }

    /**
     * Log a document deletion event.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $meta
     */
    public function deleted(Model $auditable, ?array $before = null, array $meta = []): void
    {
        $this->log(
            $auditable,
            'deleted',
            $before ?? $this->extractSnapshot($auditable),
            null,
            $meta
        );
    }

    /**
     * Extract a snapshot of important fields from a model.
     *
     * This extracts key business fields, not all attributes.
     *
     * @return array<string, mixed>
     */
    protected function extractSnapshot(Model $model): array
    {
        $snapshot = [];

        // Common fields to capture
        $commonFields = ['id', 'status', 'total', 'grand_total', 'subtotal', 'discount', 'vat'];

        foreach ($commonFields as $field) {
            if (isset($model->$field)) {
                $snapshot[$field] = $model->$field;
            }
        }

        // Add timestamps
        if (isset($model->created_at)) {
            $snapshot['created_at'] = $model->created_at->toIso8601String();
        }

        if (isset($model->updated_at)) {
            $snapshot['updated_at'] = $model->updated_at->toIso8601String();
        }

        return $snapshot;
    }

    /**
     * Create a snapshot with specific fields.
     *
     * @param  array<string>  $fields
     * @return array<string, mixed>
     */
    public function snapshot(Model $model, array $fields = []): array
    {
        if (empty($fields)) {
            return $this->extractSnapshot($model);
        }

        $snapshot = [];
        foreach ($fields as $field) {
            if (isset($model->$field)) {
                $snapshot[$field] = $model->$field;
            }
        }

        return $snapshot;
    }
}
