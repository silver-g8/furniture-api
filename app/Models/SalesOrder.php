<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Placeholder model for SalesOrder
 * This will be fully implemented in the Sales module
 *
 * @property int $id
 * @property int $customer_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SalesOrder extends Model
{
    use HasAudit;

    /** @use HasFactory<\Database\Factories\SalesOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the customer for this sales order.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the installation orders for this sales order.
     *
     * @return HasMany<InstallationOrder, $this>
     */
    public function installationOrders(): HasMany
    {
        return $this->hasMany(InstallationOrder::class, 'sales_order_id');
    }
}
