<?php

namespace App\Domain\DbAudit\DTO;

class CheckResult
{
    /** @var CheckFinding[] */
    public array $findings = [];

    /** True when findings hit the per-check cap and the list is incomplete. */
    public bool $truncated = false;

    /** Set when a check cannot run at all (e.g. the schema has nothing to inspect). */
    public bool $skipped = false;

    public function __construct(
        public string $checkKey,
        public int $limit = 500,
    ) {}

    public function add(CheckFinding $f): bool
    {
        if (count($this->findings) >= $this->limit) {
            $this->truncated = true;

            return false;
        }

        $this->findings[] = $f;

        return true;
    }

    public function skip(): self
    {
        $this->skipped = true;

        return $this;
    }

    public function isAtLimit(): bool
    {
        return count($this->findings) >= $this->limit;
    }

    public function passed(): bool
    {
        return $this->findings === [];
    }

    public function worstSeverity(): ?string
    {
        $worst = null;

        foreach ($this->findings as $f) {
            if ($f->severity === Severity::CRITICAL) {
                return Severity::CRITICAL;
            }

            if ($f->severity === Severity::WARNING) {
                $worst = Severity::WARNING;
            } elseif ($worst === null) {
                $worst = $f->severity;
            }
        }

        return $worst;
    }
}
