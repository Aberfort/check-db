<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class DuplicatesCheck implements DbCheck
{
    public function key(): string { return 'duplicates'; }

    public function title(): string { return 'Дублікати'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        /** @var array<string, array<int, string>> $uniqueRules */
        $uniqueRules = config('db_audit.unique_rules', []);

        foreach ($uniqueRules as $table => $cols) {
            if (!$db->tableExists($table) || empty($cols)) {
                continue;
            }

            $t = str_replace('"', '""', $table);
            $colsSafe = array_map(fn($c) => '"' . str_replace('"', '""', $c) . '"', $cols);
            $groupBy = implode(',', $colsSafe);

            $rows = $db->query(
                'SELECT ' . $groupBy . ', COUNT(*) AS c FROM "' . $t . '" GROUP BY ' . $groupBy . ' HAVING c > 1 LIMIT 50'
            );

            foreach ($rows as $r) {
                $meta = ['count' => (int) $r['c'], 'keys' => []];
                foreach ($cols as $c) {
                    $meta['keys'][$c] = $r[$c] ?? null;
                }

                $res->add(new CheckFinding(
                    severity: 'warning',
                    message: "Знайдено дублікати у {$table} по (" . implode(', ', $cols) . ")",
                    table: $table,
                    meta: $meta
                ));
            }
        }

        return $res;
    }
}
