<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApPayment extends Model
{
    use HasAudit, HasFactory;

    protected $table = 'ap_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'payment_no',
        'payment_date',
        'total_amount',
        'payment_method',
        'reference',
        'reference_no',
        'status',
        'note',
        'posted_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the supplier that owns the payment.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the payment allocations for the payment.
     *
     * @return HasMany<ApPaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(ApPaymentAllocation::class, 'payment_id');
    }

    /**
     * Get total allocated amount.
     */
    public function getTotalAllocatedAttribute(): float
    {
        return (float) $this->allocations()->sum('allocated_amount');
    }

    /**
     * Get unallocated amount.
     */
    public function getUnallocatedAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_allocated);
    }

    /**
     * Check if payment can be posted.
     */
    public function canBePosted(): bool
    {
        return $this->status === 'draft'
            && $this->total_amount > 0
            && $this->allocations()->count() > 0;
    }

    /**
     * Check if payment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Check if payment can be updated.
     */
    public function canBeUpdated(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Post the payment.
     */
    public function post(): bool
    {
        if (!$this->canBePosted()) {
            return false;
        }

        return $this->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);
    }

    /**
     * Cancel the payment.
     */
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Scope a query to only include draft payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include posted payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
