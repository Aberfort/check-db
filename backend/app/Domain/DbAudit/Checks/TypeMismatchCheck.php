<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * SQLite stores whatever it is given: a column declared INTEGER happily holds
 * the string "n/a". Those values survive until something tries to compare or
 * sum them, so they are worth surfacing.
 *
 * NUMERIC affinity is deliberately not inspected — DATETIME and TIMESTAMP
 * columns resolve to it and legitimately hold text.
 */
class TypeMismatchCheck extends BaseCheck
{
    private const INSPECTED_AFFINITIES = ['INTEGER', 'REAL'];

    public function key(): string
    {
        return 'type_mismatch';
    }

    public function weight(): float
    {
        return 2.0;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();
        $inspectedAnything = false;

        $this->forEachTable($db, function (string $table) use ($db, $res, &$inspectedAnything): void {
            $numeric = [];
            foreach ($db->listColumns($table) as $column) {
                if (in_array($this->affinityOf($column['type']), self::INSPECTED_AFFINITIES, true)) {
                    $numeric[] = $column['name'];
                }
            }

            if ($numeric === [] || $db->count($table) === 0) {
                return;
            }

            $inspectedAnything = true;

            // One aggregate pass per table rather than one query per column.
            $selects = [];
            foreach ($numeric as $i => $name) {
                $selects[] = 'SUM(CASE WHEN typeof(' . $db->quoteIdent($name)
                    . ") IN ('text','blob') THEN 1 ELSE 0 END) AS c{$i}";
            }

            $counts = $db->query(
                'SELECT ' . implode(', ', $selects) . ' FROM ' . $db->quoteIdent($table)
            )[0] ?? [];

            foreach ($numeric as $i => $name) {
                $bad = (int) ($counts["c{$i}"] ?? 0);
                if ($bad === 0) {
                    continue;
                }

                $sample = $db->query(
                    'SELECT ' . $db->quoteIdent($name) . ' AS v FROM ' . $db->quoteIdent($table)
                    . ' WHERE typeof(' . $db->quoteIdent($name) . ") IN ('text','blob') LIMIT 1"
                );

                $res->add(new CheckFinding(
                    severity: Severity::CRITICAL,
                    messageKey: 'type_mismatch.column',
                    params: ['table' => $table, 'column' => $name, 'count' => $bad],
                    table: $table,
                    column: $name,
                    meta: [
                        'affected_rows' => $bad,
                        'sample_value' => isset($sample[0]['v']) ? (string) $sample[0]['v'] : null,
                    ],
                ));
            }
        });

        return $inspectedAnything ? $res : $res->skip();
    }
}
