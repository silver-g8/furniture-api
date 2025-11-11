<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianTeamMember extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'technician_team_members';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'team_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'technician_id',
        'role',
        'joined_at',
        'left_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => TeamRole::class,
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    /**
     * Get the team that this membership belongs to.
     *
     * @return BelongsTo<TechnicianTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(TechnicianTeam::class, 'team_id');
    }

    /**
     * Get the technician (user) for this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Check if this member is currently active (not left).
     */
    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Check if this member is the team lead.
     */
    public function isLead(): bool
    {
        return $this->role === TeamRole::Lead;
    }

    /**
     * Scope to get only active members.
     *
     * @param  Builder<TechnicianTeamMember>  $query
     * @return Builder<TechnicianTeamMember>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }

    /**
     * Scope to get only leads.
     *
     * @param  Builder<TechnicianTeamMember>  $query
     * @return Builder<TechnicianTeamMember>
     */
    public function scopeLeads(Builder $query): Builder
    {
        return $query->where('role', TeamRole::Lead);
    }
}
