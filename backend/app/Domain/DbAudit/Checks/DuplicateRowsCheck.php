<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * Rows that are identical once the primary key is ignored — the "same record
 * inserted twice under a different id" case that a unique index would not catch.
 */
class DuplicateRowsCheck extends BaseCheck
{
    public function key(): string
    {
        return 'duplicate_rows';
    }

    public function weight(): float
    {
        return 1.5;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();
        $inspectedAnything = false;

        $this->forEachTable($db, function (string $table) use ($db, $res, &$inspectedAnything): void {
            if ($res->isAtLimit() || $db->count($table) < 2) {
                return;
            }

            $comparable = [];
            foreach ($db->listColumns($table) as $column) {
                if ($column['pk'] === 0) {
                    $comparable[] = $column['name'];
                }
            }

            if ($comparable === []) {
                return;
            }

            $inspectedAnything = true;

            $quoted = array_map(static fn ($c) => $db->quoteIdent($c), $comparable);
            $groupBy = implode(', ', $quoted);

            $rows = $db->query(
                'SELECT ' . $groupBy . ', COUNT(*) AS occurrences FROM ' . $db->quoteIdent($table)
                . ' GROUP BY ' . $groupBy . ' HAVING occurrences > 1 ORDER BY occurrences DESC LIMIT 20'
            );

            foreach ($rows as $row) {
                $occurrences = (int) ($row['occurrences'] ?? 0);
                unset($row['occurrences']);

                $added = $res->add(new CheckFinding(
                    severity: Severity::WARNING,
                    messageKey: 'duplicate_rows.group',
                    params: ['table' => $table, 'count' => $occurrences],
                    table: $table,
                    meta: ['occurrences' => $occurrences, 'values' => $this->preview($row)],
                ));

                if (! $added) {
                    return;
                }
            }
        });

        return $inspectedAnything ? $res : $res->skip();
    }

    /** Keep the stored sample small — a duplicate group can span very wide rows. */
    private function preview(array $row): array
    {
        $out = [];

        foreach (array_slice($row, 0, 5, true) as $key => $value) {
            $value = is_scalar($value) ? (string) $value : null;

            if ($value !== null && mb_strlen($value) > 120) {
                $value = mb_substr($value, 0, 120) . '…';
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
