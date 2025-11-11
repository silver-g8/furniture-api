<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamRole: string
{
    case Lead = 'lead';
    case Member = 'member';

    /**
     * Get human-readable label for the role
     */
    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Team Lead',
            self::Member => 'Team Member',
        };
    }

    /**
     * Check if this is a lead role
     */
    public function isLead(): bool
    {
        return $this === self::Lead;
    }

    /**
     * Get all valid roles
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
