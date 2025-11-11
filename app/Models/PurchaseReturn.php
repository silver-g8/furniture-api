<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasAudit;

    /** @use HasFactory<\Database\Factories\PurchaseReturnFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_id',
        'warehouse_id',
        'returned_at',
        'reason',
        'status',
        'total',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'returned_at' => 'datetime',
        'total' => 'decimal:2',
    ];

    /**
     * Get the purchase that owns the purchase return.
     *
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the warehouse that owns the purchase return.
     *
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the items for the purchase return.
     *
     * @return HasMany<PurchaseReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /**
     * Check if the purchase return is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the purchase return is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the purchase return can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if the purchase return can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->isDraft() && $this->items()->count() > 0;
    }

    /**
     * Approve the purchase return.
     */
    public function approve(): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'status' => 'approved',
            'returned_at' => now(),
        ]);
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): void
    {
        $total = $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $this->update(['total' => $total]);
    }

    /**
     * Scope a query to only include draft purchase returns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include approved purchase returns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
