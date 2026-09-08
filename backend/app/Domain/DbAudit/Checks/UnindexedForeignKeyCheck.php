<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * SQLite indexes the parent side of a foreign key but never the child side, so
 * an unindexed FK column turns every join and every parent delete into a scan.
 */
class UnindexedForeignKeyCheck extends BaseCheck
{
    public function key(): string
    {
        return 'unindexed_foreign_key';
    }

    public function weight(): float
    {
        return 1.0;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();
        $sawForeignKey = false;

        $this->forEachTable($db, function (string $table) use ($db, $res, &$sawForeignKey): void {
            $foreignKeys = $db->foreignKeys($table);
            if ($foreignKeys === []) {
                return;
            }

            $sawForeignKey = true;

            // Only the first column of an index can serve a single-column lookup.
            $indexedLeadColumns = [];
            foreach ($db->indexes($table) as $index) {
                if (isset($index['columns'][0])) {
                    $indexedLeadColumns[$index['columns'][0]] = true;
                }
            }

            foreach ($db->listColumns($table) as $column) {
                if ($column['pk'] > 0) {
                    $indexedLeadColumns[$column['name']] = true;
                }
            }

            foreach ($foreignKeys as $fk) {
                if ($fk['column'] === '' || isset($indexedLeadColumns[$fk['column']])) {
                    continue;
                }

                $res->add(new CheckFinding(
                    severity: Severity::WARNING,
                    messageKey: 'unindexed_foreign_key.column',
                    params: ['table' => $table, 'column' => $fk['column'], 'parent' => $fk['parentTable']],
                    table: $table,
                    column: $fk['column'],
                    meta: ['parent_table' => $fk['parentTable']],
                ));
            }
        });

        return $sawForeignKey ? $res : $res->skip();
    }
}
