<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArInvoice extends Model
{
    use HasAudit, HasFactory;

    protected $table = 'ar_invoices';

    protected $fillable = [
        'customer_id',
        'sales_order_id',
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

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'open_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ArReceiptAllocation::class, 'invoice_id');
    }

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
    }
}

