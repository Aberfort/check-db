<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * A column holding one distinct value across every row carries no information —
 * usually a flag that was never used or a default nobody overrides.
 */
class ConstantColumnCheck extends BaseCheck
{
    public function key(): string
    {
        return 'constant_column';
    }

    public function weight(): float
    {
        return 0.5;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();
        $minRows = (int) config('db_audit.limits.constant_column_min_rows', 5);
        $inspectedAnything = false;

        $this->forEachTable($db, function (string $table) use ($db, $res, $minRows, &$inspectedAnything): void {
            $rows = $db->count($table);
            if ($rows < $minRows) {
                return;
            }

            $columns = $db->listColumns($table);
            if ($columns === []) {
                return;
            }

            $inspectedAnything = true;

            $selects = [];
            foreach ($columns as $i => $column) {
                $selects[] = 'COUNT(DISTINCT '.$db->quoteIdent($column['name']).") AS c{$i}";
            }

            $counts = $db->query(
                'SELECT '.implode(', ', $selects).' FROM '.$db->quoteIdent($table)
            )[0] ?? [];

            foreach ($columns as $i => $column) {
                // Primary keys are distinct by definition; all-NULL columns are
                // reported by the empty-values check instead.
                if ($column['pk'] > 0 || (int) ($counts["c{$i}"] ?? 0) !== 1) {
                    continue;
                }

                $value = $db->scalar(
                    'SELECT '.$db->quoteIdent($column['name']).' FROM '.$db->quoteIdent($table)
                    .' WHERE '.$db->quoteIdent($column['name']).' IS NOT NULL LIMIT 1'
                );

                $res->add(new CheckFinding(
                    severity: Severity::WARNING,
                    messageKey: 'constant_column.column',
                    params: ['table' => $table, 'column' => $column['name']],
                    table: $table,
                    column: $column['name'],
                    meta: ['value' => is_scalar($value) ? (string) $value : null, 'total_rows' => $rows],
                ));
            }
        });

        return $inspectedAnything ? $res : $res->skip();
    }
}
