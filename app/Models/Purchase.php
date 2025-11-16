<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Purchase extends Model
{
    use HasAudit;

    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'status',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Get the supplier that owns the purchase.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the items for the purchase.
     *
     * @return HasMany<PurchaseItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the goods receipt for the purchase.
     *
     * @return HasOne<GoodsReceipt, $this>
     */
    public function goodsReceipt(): HasOne
    {
        return $this->hasOne(GoodsReceipt::class);
    }

    /**
     * Get the AP invoices for the purchase.
     *
     * @return HasMany<ApInvoice, $this>
     */
    public function apInvoices(): HasMany
    {
        return $this->hasMany(ApInvoice::class, 'purchase_id');
    }

    /**
     * Check if the purchase is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the purchase is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the purchase can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if the purchase can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->isDraft() && $this->items()->count() > 0;
    }

    /**
     * Approve the purchase.
     */
    public function approve(): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        return $this->update(['status' => 'approved']);
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total');
        $grandTotal = $subtotal - $this->discount + $this->tax;

        $this->update([
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * Scope a query to only include draft purchases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include approved purchases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
