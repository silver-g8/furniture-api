<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AuditLog Model
 *
 * Tracks document-level changes for key business entities.
 *
 * @property int $id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $action
 * @property int|null $user_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model $auditable
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'user_id',
        'ip',
        'user_agent',
        'before',
        'after',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the auditable entity (polymorphic).
     *
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by auditable type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope to filter by auditable ID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeForDocument($query, int $id)
    {
        return $query->where('auditable_id', $id);
    }

    /**
     * Scope to filter by action.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AuditLog>  $query
     * @param  string|array<string>  $action
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeForAction($query, $action)
    {
        if (is_array($action)) {
            return $query->whereIn('action', $action);
        }

        return $query->where('action', $action);
    }

    /**
     * Scope to filter by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AuditLog>
     */
    public function scopeDateRange($query, ?string $from = null, ?string $to = null)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}
