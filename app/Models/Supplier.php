<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'is_active',
        'payment_terms',
        'credit_days',
        'credit_limit',
        'tax_id',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'credit_days' => 'integer',
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Get the purchases for the supplier.
     *
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the AP invoices for the supplier.
     *
     * @return HasMany<ApInvoice, $this>
     */
    public function apInvoices(): HasMany
    {
        return $this->hasMany(ApInvoice::class, 'supplier_id');
    }

    /**
     * Get the AP payments for the supplier.
     *
     * @return HasMany<ApPayment, $this>
     */
    public function apPayments(): HasMany
    {
        return $this->hasMany(ApPayment::class, 'supplier_id');
    }

    /**
     * Get total outstanding balance for the supplier.
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->apInvoices()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->sum('open_amount');
    }

    /**
     * Scope a query to only include active suppliers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
