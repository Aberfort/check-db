<?php

namespace App\Domain\DbAudit\DTO;

final class Severity
{
    /** Data is broken: corruption, violated constraints, values that break their own schema. */
    public const CRITICAL = 'critical';

    /** Quality or schema smell: still usable, but worth fixing. */
    public const WARNING = 'warning';

    /** Neutral observation, no action implied. */
    public const INFO = 'info';

    public const ALL = [self::CRITICAL, self::WARNING, self::INFO];

    /** Relative cost used when scoring an analysis. */
    public const WEIGHTS = [
        self::CRITICAL => 1.0,
        self::WARNING => 0.4,
        self::INFO => 0.0,
    ];

    public static function isValid(string $severity): bool
    {
        return in_array($severity, self::ALL, true);
    }
}
