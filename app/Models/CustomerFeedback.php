<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'installation_order_id',
        'customer_id',
        'overall_rating',
        'technician_rating',
        'timeliness_rating',
        'quality_rating',
        'comments',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'overall_rating' => 'integer',
        'technician_rating' => 'integer',
        'timeliness_rating' => 'integer',
        'quality_rating' => 'integer',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the installation order for this feedback.
     *
     * @return BelongsTo<InstallationOrder, $this>
     */
    public function installationOrder(): BelongsTo
    {
        return $this->belongsTo(InstallationOrder::class, 'installation_order_id');
    }

    /**
     * Get the customer who submitted this feedback.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Calculate the average rating across all dimensions.
     */
    public function getAverageRating(): float
    {
        return round(
            ($this->overall_rating +
             $this->technician_rating +
             $this->timeliness_rating +
             $this->quality_rating) / 4,
            2
        );
    }

    /**
     * Check if this feedback has a low rating (< 3).
     */
    public function isLowRated(): bool
    {
        return $this->getAverageRating() < 3.0;
    }

    /**
     * Check if any individual rating is low (< 3).
     */
    public function hasLowIndividualRating(): bool
    {
        return $this->overall_rating < 3 ||
               $this->technician_rating < 3 ||
               $this->timeliness_rating < 3 ||
               $this->quality_rating < 3;
    }

    /**
     * Scope to filter by minimum average rating.
     *
     * @param  Builder<CustomerFeedback>  $query
     * @return Builder<CustomerFeedback>
     */
    public function scopeMinimumRating(Builder $query, float $minRating): Builder
    {
        return $query->whereRaw(
            '(overall_rating + technician_rating + timeliness_rating + quality_rating) / 4 >= ?',
            [$minRating]
        );
    }

    /**
     * Scope to get low-rated feedback.
     *
     * @param  Builder<CustomerFeedback>  $query
     * @return Builder<CustomerFeedback>
     */
    public function scopeLowRated(Builder $query): Builder
    {
        return $query->whereRaw(
            '(overall_rating + technician_rating + timeliness_rating + quality_rating) / 4 < 3'
        );
    }

    /**
     * Scope to get high-rated feedback (>= 4).
     *
     * @param  Builder<CustomerFeedback>  $query
     * @return Builder<CustomerFeedback>
     */
    public function scopeHighRated(Builder $query): Builder
    {
        return $query->whereRaw(
            '(overall_rating + technician_rating + timeliness_rating + quality_rating) / 4 >= 4'
        );
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<CustomerFeedback>  $query
     * @return Builder<CustomerFeedback>
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('submitted_at', [$startDate, $endDate]);
    }
}
