<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InstallationStatus;
use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationOrder extends Model
{
    use HasAudit;

    /** @use HasFactory<\Database\Factories\InstallationOrderFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sales_order_id',
        'customer_id',
        'installation_address_id',
        'installation_address_override',
        'installation_contact_name',
        'installation_contact_phone',
        'status',
        'deletion_reason',
        'sla_paused_at',
        'sla_resumed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => InstallationStatus::class,
        'sla_paused_at' => 'datetime',
        'sla_resumed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the sales order that this installation belongs to.
     *
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * Get the customer for this installation.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the installation address.
     *
     * @return BelongsTo<CustomerAddress, $this>
     */
    public function installationAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'installation_address_id');
    }

    /**
     * Get all schedules for this installation.
     *
     * @return HasMany<InstallationSchedule, $this>
     */
    public function installationSchedules(): HasMany
    {
        return $this->hasMany(InstallationSchedule::class, 'installation_order_id');
    }

    /**
     * Get all photos for this installation.
     *
     * @return HasMany<InstallationPhoto, $this>
     */
    public function installationPhotos(): HasMany
    {
        return $this->hasMany(InstallationPhoto::class, 'installation_order_id');
    }

    /**
     * Get the customer feedback for this installation.
     *
     * @return HasOne<CustomerFeedback, $this>
     */
    public function customerFeedback(): HasOne
    {
        return $this->hasOne(CustomerFeedback::class, 'installation_order_id');
    }

    /**
     * Get the active (most recent) schedule for this installation.
     */
    public function getActiveSchedule(): ?InstallationSchedule
    {
        return $this->installationSchedules()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if transition to a new status is valid.
     */
    public function canTransitionTo(InstallationStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    /**
     * Check if installation has required 'after' photos for completion.
     */
    public function hasRequiredAfterPhotos(): bool
    {
        return $this->installationPhotos()
            ->where('category', 'after')
            ->exists();
    }

    /**
     * Check if SLA is currently paused.
     */
    public function isSlaPaused(): bool
    {
        return $this->sla_paused_at !== null && $this->sla_resumed_at === null;
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<InstallationOrder>  $query
     * @return Builder<InstallationOrder>
     */
    public function scopeStatus(Builder $query, InstallationStatus|string $status): Builder
    {
        if (is_string($status)) {
            $status = InstallationStatus::from($status);
        }

        return $query->where('status', $status);
    }

    /**
     * Scope to filter by customer.
     *
     * @param  Builder<InstallationOrder>  $query
     * @return Builder<InstallationOrder>
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<InstallationOrder>  $query
     * @return Builder<InstallationOrder>
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
