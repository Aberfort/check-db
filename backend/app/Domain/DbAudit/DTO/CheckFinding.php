<?php

namespace App\Domain\DbAudit\DTO;

/**
 * Findings are stored language-neutral: a translation key plus its parameters.
 * The UI language can then change without re-running the analysis.
 */
class CheckFinding
{
    public function __construct(
        public string $severity,
        public string $messageKey,
        public array $params = [],
        public ?string $table = null,
        public ?string $column = null,
        public ?array $rowRef = null,
        public ?array $meta = null,
        /** Optional grouping label so histograms can be aggregated in SQL. */
        public ?string $bucket = null,
    ) {}
}
