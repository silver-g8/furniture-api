<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationSchedule extends Model
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
        'team_id',
        'scheduled_at',
        'completed_at',
        'estimated_duration_minutes',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_duration_minutes' => 'integer',
    ];

    /**
     * Get the installation order for this schedule.
     *
     * @return BelongsTo<InstallationOrder, $this>
     */
    public function installationOrder(): BelongsTo
    {
        return $this->belongsTo(InstallationOrder::class, 'installation_order_id');
    }

    /**
     * Get the technician team for this schedule.
     *
     * @return BelongsTo<TechnicianTeam, $this>
     */
    public function technicianTeam(): BelongsTo
    {
        return $this->belongsTo(TechnicianTeam::class, 'team_id');
    }

    /**
     * Alias for technicianTeam relationship.
     *
     * @return BelongsTo<TechnicianTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->technicianTeam();
    }

    /**
     * Check if scheduled time is within business hours.
     * Business hours: Mon-Sat 08:00-18:00 (Asia/Bangkok)
     */
    public function isBusinessHours(): bool
    {
        $scheduledAt = $this->scheduled_at;

        // Check if it's Sunday (0 = Sunday in Carbon)
        if ($scheduledAt->dayOfWeek === Carbon::SUNDAY) {
            return false;
        }

        // Check if time is between 08:00 and 18:00
        $hour = $scheduledAt->hour;

        return $hour >= 8 && $hour < 18;
    }

    /**
     * Check if this schedule is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Get the estimated end time.
     */
    public function getEstimatedEndTime(): Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->estimated_duration_minutes);
    }

    /**
     * Scope to filter by team.
     *
     * @param  Builder<InstallationSchedule>  $query
     * @return Builder<InstallationSchedule>
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder<InstallationSchedule>  $query
     * @return Builder<InstallationSchedule>
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get upcoming schedules.
     *
     * @param  Builder<InstallationSchedule>  $query
     * @return Builder<InstallationSchedule>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>', now())
            ->whereNull('completed_at')
            ->orderBy('scheduled_at', 'asc');
    }

    /**
     * Scope to get completed schedules.
     *
     * @param  Builder<InstallationSchedule>  $query
     * @return Builder<InstallationSchedule>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }
}
