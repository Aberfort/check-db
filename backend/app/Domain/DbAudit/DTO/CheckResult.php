<?php

namespace App\Domain\DbAudit\DTO;

class CheckResult
{
    /** @var CheckFinding[] */
    public array $findings = [];

    public function __construct(
        public string $checkKey,
        public string $title,
    ) {}

    public function add(CheckFinding $f): void
    {
        $this->findings[] = $f;
    }
}
