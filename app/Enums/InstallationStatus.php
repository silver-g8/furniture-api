<?php

declare(strict_types=1);

namespace App\Enums;

enum InstallationStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case PendingParts = 'pending_parts';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::NoShow => 'No Show',
            self::PendingParts => 'Pending Parts',
        };
    }

    /**
     * Check if transition to another status is valid
     * Based on state machine rules from data-model.md
     */
    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Draft => in_array($to, [self::Scheduled]),
            self::Scheduled => in_array($to, [self::InProgress, self::NoShow]),
            self::InProgress => in_array($to, [self::Completed, self::PendingParts]),
            self::NoShow => in_array($to, [self::Scheduled]),
            self::PendingParts => in_array($to, [self::Scheduled]),
            self::Completed => false, // Terminal state
        };
    }

    /**
     * Check if this is a terminal state
     */
    public function isTerminal(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Check if SLA should be paused for this status
     */
    public function shouldPauseSla(): bool
    {
        return in_array($this, [self::NoShow, self::PendingParts]);
    }

    /**
     * Get all valid statuses
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
