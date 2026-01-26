<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class MissingTablesCheck implements DbCheck
{
    public function key(): string { return 'missing_tables'; }

    public function title(): string { return 'Відсутні таблиці'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        /** @var array<int, string> $requiredTables */
        $requiredTables = config('db_audit.required_tables', []);

        foreach ($requiredTables as $t) {
            if (!$db->tableExists($t)) {
                $res->add(new CheckFinding(
                    severity: 'critical',
                    message: "Таблиця '{$t}' відсутня",
                    table: $t,
                ));
            }
        }

        return $res;
    }
}
