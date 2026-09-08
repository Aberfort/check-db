<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

class MissingPrimaryKeyCheck extends BaseCheck
{
    public function key(): string
    {
        return 'missing_primary_key';
    }

    public function weight(): float
    {
        return 1.5;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();

        $this->forEachTable($db, function (string $table) use ($db, $res): void {
            $columns = $db->listColumns($table);
            if ($columns === []) {
                return;
            }

            foreach ($columns as $column) {
                if ($column['pk'] > 0) {
                    return;
                }
            }

            $res->add(new CheckFinding(
                severity: Severity::WARNING,
                messageKey: 'missing_primary_key.table',
                params: ['table' => $table],
                table: $table,
            ));
        });

        return $res;
    }
}
