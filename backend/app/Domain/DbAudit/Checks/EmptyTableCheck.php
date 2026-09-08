<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

class EmptyTableCheck extends BaseCheck
{
    public function key(): string
    {
        return 'empty_table';
    }

    public function weight(): float
    {
        return 0.5;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();

        $this->forEachTable($db, function (string $table) use ($db, $res): void {
            if ($db->count($table) > 0) {
                return;
            }

            $res->add(new CheckFinding(
                severity: Severity::INFO,
                messageKey: 'empty_table.table',
                params: ['table' => $table],
                table: $table,
            ));
        });

        return $res;
    }
}
