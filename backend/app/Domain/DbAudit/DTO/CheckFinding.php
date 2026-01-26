<?php

namespace App\Domain\DbAudit\DTO;

class CheckFinding
{
    public function __construct(
        public string $severity, // warning|critical
        public string $message,
        public ?string $table = null,
        public ?string $column = null,
        public ?array $rowRef = null,
        public ?array $meta = null,
    ) {}
}
