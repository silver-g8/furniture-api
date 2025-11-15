<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'stock_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the stock that owns the movement.
     *
     * @return BelongsTo<Stock, $this>
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the user that created the movement.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (polymorphic).
     *
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Scope a query to filter by warehouse.
     *
     * @param  Builder<StockMovement>  $query
     * @param  int  $warehouseId
     * @return Builder<StockMovement>
     */
    public function scopeFilterByWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->whereHas('stock', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        });
    }

    /**
     * Scope a query to filter by product.
     *
     * @param  Builder<StockMovement>  $query
     * @param  int  $productId
     * @return Builder<StockMovement>
     */
    public function scopeFilterByProduct(Builder $query, int $productId): Builder
    {
        return $query->whereHas('stock', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        });
    }

    /**
     * Scope a query to filter by stock.
     *
     * @param  Builder<StockMovement>  $query
     * @param  int  $stockId
     * @return Builder<StockMovement>
     */
    public function scopeFilterByStock(Builder $query, int $stockId): Builder
    {
        return $query->where('stock_id', $stockId);
    }

    /**
     * Scope a query to filter by type.
     *
     * @param  Builder<StockMovement>  $query
     * @param  string  $type
     * @return Builder<StockMovement>
     */
    public function scopeFilterByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by reference.
     *
     * @param  Builder<StockMovement>  $query
     * @param  string  $referenceType
     * @param  int|null  $referenceId
     * @return Builder<StockMovement>
     */
    public function scopeFilterByReference(Builder $query, string $referenceType, ?int $referenceId = null): Builder
    {
        $query = $query->where('reference_type', $referenceType);

        if ($referenceId !== null) {
            $query->where('reference_id', $referenceId);
        }

        return $query;
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  Builder<StockMovement>  $query
     * @param  string|null  $fromDate
     * @param  string|null  $toDate
     * @return Builder<StockMovement>
     */
    public function scopeFilterByDateRange(Builder $query, ?string $fromDate = null, ?string $toDate = null): Builder
    {
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Scope a query to filter by user.
     *
     * @param  Builder<StockMovement>  $query
     * @param  int  $userId
     * @return Builder<StockMovement>
     */
    public function scopeFilterByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to search by product name or warehouse name.
     *
     * @param  Builder<StockMovement>  $query
     * @param  string  $search
     * @return Builder<StockMovement>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('stock.product', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        })->orWhereHas('stock.warehouse', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }

    /**
     * Get the balance before this movement.
     * This calculates the stock quantity before this movement occurred.
     *
     * @return int
     */
    public function getBalanceBeforeAttribute(): int
    {
        $stock = $this->stock;
        if (! $stock) {
            return 0;
        }

        // Get sum of all movements before this one
        $movementsBefore = self::where('stock_id', $this->stock_id)
            ->where('id', '<', $this->id)
            ->get();

        $balance = 0;
        foreach ($movementsBefore as $movement) {
            if ($movement->type === 'in') {
                $balance += $movement->quantity;
            } else {
                $balance -= $movement->quantity;
            }
        }

        return $balance;
    }

    /**
     * Get the balance after this movement.
     * This calculates the stock quantity after this movement occurred.
     *
     * @return int
     */
    public function getBalanceAfterAttribute(): int
    {
        $balanceBefore = $this->balance_before;

        if ($this->type === 'in') {
            return $balanceBefore + $this->quantity;
        }

        return $balanceBefore - $this->quantity;
    }
}
