<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Customer model
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $payment_type
 * @property string $customer_group
 * @property string|null $address
 * @property bool $is_active
 * @property string|null $notes
 * @property float|null $credit_limit
 * @property int|null $credit_term_days
 * @property float $outstanding_balance
 * @property string|null $credit_note
 * @property bool $is_credit
 * @property bool $is_over_credit_limit
 * @property string $customer_group_label
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'is_active',
        'notes',
        'payment_type',
        'customer_group',
        'credit_limit',
        'credit_term_days',
        'outstanding_balance',
        'credit_note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'customer_group' => 'string',
        'credit_limit' => 'decimal:2',
        'credit_term_days' => 'integer',
        'outstanding_balance' => 'decimal:2',
    ];

    protected $appends = [
        'is_credit',
        'is_over_credit_limit',
        'customer_group_label',
    ];

    /**
     * Get the orders for this customer.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the sales orders for this customer.
     *
     * @return HasMany<SalesOrder, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Get the addresses for this customer.
     *
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get the installation orders for this customer.
     *
     * @return HasMany<InstallationOrder, $this>
     */
    public function installationOrders(): HasMany
    {
        return $this->hasMany(InstallationOrder::class);
    }

    /**
     * Get the feedback submitted by this customer.
     *
     * @return HasMany<CustomerFeedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    /**
     * Get the AR invoices for this customer.
     *
     * @return HasMany<ArInvoice, $this>
     */
    public function arInvoices(): HasMany
    {
        return $this->hasMany(ArInvoice::class);
    }

    /**
     * Get the AR receipts for this customer.
     *
     * @return HasMany<ArReceipt, $this>
     */
    public function arReceipts(): HasMany
    {
        return $this->hasMany(ArReceipt::class);
    }

    /**
     * Get the purchased items for this customer through sales orders.
     *
     * @return HasManyThrough<SalesOrderItem, SalesOrder, $this>
     */
    public function purchasedItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            SalesOrderItem::class,
            SalesOrder::class,
            'customer_id',     // SalesOrder.customer_id
            'sales_order_id',  // SalesOrderItem.sales_order_id
            'id',              // Customer.id
            'id'               // SalesOrder.id
        );
    }

    /**
     * Check if customer is on credit payment type.
     */
    public function getIsCreditAttribute(): bool
    {
        return $this->payment_type === 'credit';
    }

    /**
     * Check if customer has exceeded credit limit.
     */
    public function getIsOverCreditLimitAttribute(): bool
    {
        if (! $this->is_credit || $this->credit_limit === null) {
            return false;
        }

        return $this->outstanding_balance > $this->credit_limit;
    }

    /**
     * Get the customer group label in Thai.
     */
    public function getCustomerGroupLabelAttribute(): string
    {
        if (! $this->customer_group) {
            return 'ไม่ระบุ';
        }

        return match ($this->customer_group) {
            'personal' => 'บุคคลธรรมดา',
            'government' => 'ข้าราชการ / หน่วยงานรัฐ',
            'organization' => 'องค์กร / บริษัท',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Scope a query to filter by customer group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroup($query, $group)
    {
        if ($group) {
            return $query->where('customer_group', $group);
        }

        return $query;
    }

    /* -------------------------
     |  Scopes
     * ------------------------*/

    /**
     * ค้นหาจาก keyword (code, name, phone, email)
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword) {
            return $query;
        }

        $kw = '%' . $keyword . '%';

        return $query->where(function (Builder $q) use ($kw): void {
            $q->where('code', 'like', $kw)
                ->orWhere('name', 'like', $kw)
                ->orWhere('phone', 'like', $kw)
                ->orWhere('email', 'like', $kw);
        });
    }

    /**
     * filter ประเภทชำระ (cash / credit)
     */
    public function scopePaymentType(Builder $query, ?string $type): Builder
    {
        if (! $type) {
            return $query;
        }

        return $query->where('payment_type', $type);
    }

    /**
     * filter กลุ่มลูกค้า (personal / government / organization)
     */
    public function scopeCustomerGroup(Builder $query, ?string $group): Builder
    {
        if (! $group) {
            return $query;
        }

        return $query->where('customer_group', $group);
    }

    /**
     * filter ลูกค้าที่มียอดค้างชำระ > 0
     */
    public function scopeHasOutstanding(Builder $query, ?bool $hasOutstanding): Builder
    {
        if ($hasOutstanding === null) {
            return $query;
        }

        if ($hasOutstanding) {
            return $query->where('outstanding_balance', '>', 0);
        }

        return $query->where('outstanding_balance', '=', 0);
    }

    /**
     * filter ลูกค้าที่เกินวงเงินเครดิต
     */
    public function scopeOverCreditLimit(Builder $query, ?bool $over): Builder
    {
        if ($over === null) {
            return $query;
        }

        if ($over) {
            return $query
                ->where('payment_type', 'credit')
                ->whereNotNull('credit_limit')
                ->whereColumn('outstanding_balance', '>', 'credit_limit');
        }

        return $query;
    }
}
