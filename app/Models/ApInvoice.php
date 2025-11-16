<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApInvoice extends Model
{
    use HasAudit, HasFactory;

    protected $table = 'ap_invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'purchase_id',
        'invoice_no',
        'invoice_date',
        'due_date',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_total',
        'open_amount',
        'currency',
        'status',
        'reference_type',
        'reference_id',
        'note',
        'issued_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'grand_total' => 'float',
        'paid_total' => 'float',
        'open_amount' => 'float',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'is_overdue',
    ];

    /**
     * Get the supplier that owns the invoice.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase order that owns the invoice.
     *
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the payment allocations for the invoice.
     *
     * @return HasMany<ApPaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(ApPaymentAllocation::class, 'invoice_id');
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && $this->open_amount > 0
            && $this->due_date->isPast();
    }

    /**
     * Check if invoice can be issued.
     */
    public function canBeIssued(): bool
    {
        return $this->status === 'draft' && $this->grand_total > 0;
    }

    /**
     * Check if invoice can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'issued', 'partially_paid'])
            && $this->paid_total == 0;
    }

    /**
     * Check if invoice can be updated.
     */
    public function canBeUpdated(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->status === 'paid' || ($this->open_amount == 0 && $this->grand_total > 0);
    }

    /**
     * Calculate total amount from subtotal, discount, and tax.
     */
    public function calculateTotal(): void
    {
        $this->grand_total = $this->subtotal_amount - $this->discount_amount + $this->tax_amount;
    }

    /**
     * Recalculate open amount based on paid total.
     */
    public function recalculateOpenAmount(): void
    {
        $this->open_amount = max(0, $this->grand_total - $this->paid_total);

        // Update status based on payment
        if ($this->paid_total == 0) {
            if ($this->status !== 'draft' && $this->status !== 'cancelled') {
                $this->status = 'issued';
            }
        } elseif ($this->open_amount == 0) {
            $this->status = 'paid';
        } else {
            $this->status = 'partially_paid';
        }
    }

    /**
     * Issue the invoice.
     */
    public function issue(): bool
    {
        if (!$this->canBeIssued()) {
            return false;
        }

        return $this->update([
            'status' => 'issued',
            'issued_at' => now(),
        ]);
    }

    /**
     * Cancel the invoice.
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
     * Scope a query to only include draft invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include issued invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    /**
     * Scope a query to only include unpaid invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeUnpaid($query)
    {
        return $query->where('open_amount', '>', 0)
            ->whereIn('status', ['issued', 'partially_paid']);
    }

    /**
     * Scope a query to only include overdue invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', \now())
            ->where('open_amount', '>', 0)
            ->whereIn('status', ['issued', 'partially_paid']);
    }
}
