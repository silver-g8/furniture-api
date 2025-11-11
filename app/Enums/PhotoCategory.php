<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoCategory: string
{
    case Before = 'before';
    case During = 'during';
    case After = 'after';
    case Issue = 'issue';

    /**
     * Get human-readable label for the category
     */
    public function label(): string
    {
        return match ($this) {
            self::Before => 'Before Installation',
            self::During => 'During Installation',
            self::After => 'After Installation',
            self::Issue => 'Issue/Problem',
        };
    }

    /**
     * Get all valid categories
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
