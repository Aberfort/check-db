<?php

namespace App\Domain\DbAudit\Contracts;

use App\Domain\DbAudit\DTO\CheckResult;

interface DbCheck
{
    /** Stable identifier, also used as the i18n key for the check's title. */
    public function key(): string;

    /** Relative importance when scoring an analysis. */
    public function weight(): float;

    public function run(DbAdapter $db): CheckResult;
}
