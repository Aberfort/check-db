<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class RequiredFieldsCheck implements DbCheck
{
    public function key(): string { return 'required_fields'; }

    public function title(): string { return 'Порожні/NULL обовʼязкові поля'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        /** @var array<string, array<int, string>> $requiredFields */
        $requiredFields = config('db_audit.required_fields', []);

        foreach ($requiredFields as $table => $cols) {
            if (!$db->tableExists($table)) {
                continue;
            }

            foreach ($cols as $col) {
                $t = str_replace('"', '""', $table);
                $c = str_replace('"', '""', $col);

                // MVP: ліміт на приклади
                $rows = $db->query(
                    'SELECT rowid as __rowid FROM "' . $t . '" WHERE "' . $c . '" IS NULL OR TRIM(CAST("' . $c . '" AS TEXT)) = "" LIMIT 50'
                );

                foreach ($rows as $r) {
                    $res->add(new CheckFinding(
                        severity: 'warning',
                        message: "Порожнє значення у {$table}.{$col}",
                        table: $table,
                        column: $col,
                        rowRef: ['rowid' => (int) $r['__rowid']],
                    ));
                }
            }
        }

        return $res;
    }
}
