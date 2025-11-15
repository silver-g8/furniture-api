<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArReceipt extends Model
{
    use HasAudit, HasFactory;

    protected $table = 'ar_receipts';

    protected $fillable = [
        'customer_id',
        'receipt_no',
        'receipt_date',
        'total_amount',
        'payment_method',
        'reference',
        'reference_no',
        'note',
        'status',
        'posted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ArReceiptAllocation::class, 'receipt_id');
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
     * Check if receipt can be posted.
     */
    public function canBePosted(): bool
    {
        return $this->status === 'draft'
            && $this->total_amount > 0
            && $this->allocations()->count() > 0;
    }

    /**
     * Check if receipt can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Check if receipt can be updated.
     */
    public function canBeUpdated(): bool
    {
        return $this->status === 'draft';
    }
}

