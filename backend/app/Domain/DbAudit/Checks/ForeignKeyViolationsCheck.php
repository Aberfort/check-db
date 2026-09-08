<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * Orphan rows, discovered from the database's own foreign keys rather than
 * from hand-maintained configuration.
 */
class ForeignKeyViolationsCheck extends BaseCheck
{
    public function key(): string
    {
        return 'foreign_keys';
    }

    public function weight(): float
    {
        return 3.0;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();

        $hasAnyForeignKey = false;
        foreach ($db->listTables() as $table) {
            if ($db->foreignKeys($table) !== []) {
                $hasAnyForeignKey = true;
                break;
            }
        }

        if (! $hasAnyForeignKey) {
            return $res->skip();
        }

        foreach ($db->query('PRAGMA foreign_key_check') as $row) {
            $table = (string) ($row['table'] ?? '');
            $parent = (string) ($row['parent'] ?? '');

            $column = null;
            $fkIndex = isset($row['fkid']) ? (int) $row['fkid'] : null;
            if ($fkIndex !== null) {
                $column = $db->foreignKeys($table)[$fkIndex]['column'] ?? null;
            }

            $added = $res->add(new CheckFinding(
                severity: Severity::CRITICAL,
                messageKey: 'foreign_keys.orphan_row',
                params: ['table' => $table, 'parent' => $parent, 'column' => $column ?? '?'],
                table: $table,
                column: $column,
                rowRef: isset($row['rowid']) ? ['rowid' => (int) $row['rowid']] : null,
                meta: ['parent_table' => $parent],
            ));

            if (! $added) {
                break;
            }
        }

        return $res;
    }
}
