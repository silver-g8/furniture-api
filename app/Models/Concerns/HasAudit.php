<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasAudit Trait
 *
 * Provides audit logging capabilities to document models.
 * Attach this trait to models that need document-level audit tracking.
 */
trait HasAudit
{
    /**
     * Get all audit logs for this model.
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Log an audit event for this model.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function audit(
        string $action,
        ?array $before = null,
        ?array $after = null,
        array $meta = []
    ): void {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->log($this, $action, $before, $after, $meta);
    }

    /**
     * Log a creation event.
     *
     * @param  array<string, mixed>  $meta
     */
    public function auditCreated(array $meta = []): void
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->created($this, $meta);
    }

    /**
     * Log an update event.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function auditUpdated(array $before, ?array $after = null, array $meta = []): void
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->updated($this, $before, $after, $meta);
    }

    /**
     * Log an approval event.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function auditApproved(array $before, ?array $after = null, array $meta = []): void
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->approved($this, $before, $after, $meta);
    }

    /**
     * Log a cancellation event.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $meta
     */
    public function auditCancelled(array $before, ?array $after = null, array $meta = []): void
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->cancelled($this, $before, $after, $meta);
    }

    /**
     * Log a deletion event.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $meta
     */
    public function auditDeleted(?array $before = null, array $meta = []): void
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        $logger->deleted($this, $before, $meta);
    }

    /**
     * Create a snapshot of this model's current state.
     *
     * @param  array<string>  $fields
     * @return array<string, mixed>
     */
    public function snapshot(array $fields = []): array
    {
        /** @var AuditLogger $logger */
        $logger = app(AuditLogger::class);

        return $logger->snapshot($this, $fields);
    }
}
