<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * Two different problems share one pass over the data:
 *  - a NOT NULL column filled with empty strings, which defeats the constraint;
 *  - a nullable column that is empty for almost every row, which usually means
 *    it was never populated.
 */
class EmptyValuesCheck extends BaseCheck
{
    public function key(): string
    {
        return 'empty_values';
    }

    public function weight(): float
    {
        return 1.5;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();
        $threshold = (float) config('db_audit.limits.empty_ratio_threshold', 0.9);
        $inspectedAnything = false;

        $this->forEachTable($db, function (string $table) use ($db, $res, $threshold, &$inspectedAnything): void {
            $columns = $db->listColumns($table);
            $rows = $db->count($table);

            if ($columns === [] || $rows === 0) {
                return;
            }

            $inspectedAnything = true;

            $selects = [];
            foreach ($columns as $i => $column) {
                $q = $db->quoteIdent($column['name']);
                $selects[] = "SUM(CASE WHEN {$q} IS NULL OR TRIM(CAST({$q} AS TEXT)) = '' THEN 1 ELSE 0 END) AS c{$i}";
            }

            $counts = $db->query(
                'SELECT ' . implode(', ', $selects) . ' FROM ' . $db->quoteIdent($table)
            )[0] ?? [];

            foreach ($columns as $i => $column) {
                $empty = (int) ($counts["c{$i}"] ?? 0);
                if ($empty === 0) {
                    continue;
                }

                $ratio = $empty / $rows;

                if ($column['notnull'] === 1) {
                    $res->add(new CheckFinding(
                        severity: Severity::WARNING,
                        messageKey: 'empty_values.not_null_but_blank',
                        params: ['table' => $table, 'column' => $column['name'], 'count' => $empty],
                        table: $table,
                        column: $column['name'],
                        meta: ['affected_rows' => $empty, 'total_rows' => $rows],
                    ));

                    continue;
                }

                if ($ratio >= $threshold) {
                    $res->add(new CheckFinding(
                        severity: Severity::WARNING,
                        messageKey: 'empty_values.mostly_empty',
                        params: [
                            'table' => $table,
                            'column' => $column['name'],
                            'percent' => (int) round($ratio * 100),
                        ],
                        table: $table,
                        column: $column['name'],
                        meta: ['affected_rows' => $empty, 'total_rows' => $rows],
                    ));
                }
            }
        });

        return $inspectedAnything ? $res : $res->skip();
    }
}
