<?php

namespace App\Domain\DbAudit\Contracts;

use App\Domain\DbAudit\DTO\CheckResult;

interface DbCheck
{
    public function key(): string;
    public function title(): string;

    public function run(DbAdapter $db): CheckResult;
}
