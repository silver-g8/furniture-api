<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicianTeam extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the installation schedules for this team.
     *
     * @return HasMany<InstallationSchedule, $this>
     */
    public function installationSchedules(): HasMany
    {
        return $this->hasMany(InstallationSchedule::class, 'team_id');
    }

    /**
     * Get the technicians (users) that belong to this team.
     *
     * @return BelongsToMany<User, $this>
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'technician_team_members', 'team_id', 'technician_id')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    /**
     * Get only active members (left_at is null).
     *
     * @return BelongsToMany<User, $this>
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->technicians()->whereNull('technician_team_members.left_at');
    }

    /**
     * Alias for activeMembers relationship.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->activeMembers();
    }

    /**
     * Get the lead member of the team.
     */
    public function getLeadMember(): ?User
    {
        /** @var User|null */
        return $this->activeMembers()
            ->wherePivot('role', 'lead')
            ->first();
    }

    /**
     * Check if team has at least one active member.
     */
    public function hasActiveMember(): bool
    {
        return $this->activeMembers()->exists();
    }

    /**
     * Scope to get only active teams.
     *
     * @param  Builder<TechnicianTeam>  $query
     * @return Builder<TechnicianTeam>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
