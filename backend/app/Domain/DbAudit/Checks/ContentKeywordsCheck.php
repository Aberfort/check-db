<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class ContentKeywordsCheck implements DbCheck
{
    public function key(): string
    {
        return 'content_keywords';
    }

    public function title(): string
    {
        return 'Content keywords';
    }

    public function run(DbAdapter $db): CheckResult
    {
        $result = new CheckResult($this->key(), $this->title());

        $cfg = (array) config('db_audit.content_keywords', []);

        $needles = array_values(array_filter((array) ($cfg['needles'] ?? []), fn($v) => is_string($v) && $v !== ''));
        $columnsWanted = array_values(array_filter((array) ($cfg['columns'] ?? []), fn($v) => is_string($v) && $v !== ''));

        $maxTotal = (int) ($cfg['max_findings_total'] ?? 5000);
        $maxPerTable = (int) ($cfg['max_findings_per_table'] ?? 800);

        if ($maxTotal <= 0) $maxTotal = 1;
        if ($maxPerTable <= 0) $maxPerTable = 1;

        if (!$needles || !$columnsWanted) {
            return $result;
        }

        if (!method_exists($db, 'listColumns')) {
            throw new \RuntimeException('SqliteAdapter::listColumns() is required for ContentKeywordsCheck');
        }

        $tables = $db->listTables();
        $total = 0;

        foreach ($tables as $table) {
            if ($total >= $maxTotal) break;

            $colsInfo = (array) $db->listColumns($table);
            if (!$colsInfo) continue;

            $cols = array_values(array_filter(array_map(
                fn($r) => is_array($r) ? ($r['name'] ?? null) : null,
                $colsInfo
            )));

            if (!$cols) continue;

            $colSet = array_fill_keys($cols, true);

            // які колонки реально є в цій таблиці
            $scanCols = [];
            foreach ($columnsWanted as $c) {
                if (isset($colSet[$c])) $scanCols[] = $c;
            }
            if (!$scanCols) continue;

            $hasEndpoint = isset($colSet['endpoint']);
            $tableFindings = 0;

            foreach ($scanCols as $col) {
                if ($total >= $maxTotal || $tableFindings >= $maxPerTable) break;

                foreach ($needles as $needle) {
                    if ($total >= $maxTotal || $tableFindings >= $maxPerTable) break;

                    $t = $this->qi($table);
                    $c = $this->qi($col);

                    $selectCols = 'rowid as __rowid, ' . $c . ' as __value';
                    if ($hasEndpoint) {
                        $selectCols = 'rowid as __rowid, ' . $this->qi('endpoint') . ' as endpoint, ' . $c . ' as __value';
                    }

                    $sql = 'SELECT ' . $selectCols . ' FROM ' . $t . ' WHERE CAST(' . $c . ' AS TEXT) LIKE ? LIMIT ?';
                    $rows = $db->select($sql, ['%' . $needle . '%', $maxPerTable]);

                    foreach ((array) $rows as $r) {
                        if ($total >= $maxTotal || $tableFindings >= $maxPerTable) break;

                        $endpoint = $hasEndpoint ? (string) ($r['endpoint'] ?? '') : '';
                        $rowRef = ($hasEndpoint && $endpoint !== '')
                            ? ['endpoint' => $endpoint]
                            : ['rowid' => (int) ($r['__rowid'] ?? 0)];

                        $value = array_key_exists('__value', $r) ? (string) $r['__value'] : null;

                        $result->add(new CheckFinding(
                            severity: 'critical',
                            message: $needle,
                            table: $table,
                            column: $col,
                            rowRef: $rowRef,
                            meta: [
                                'needle' => $needle,
                                'value' => $value,
                            ],
                        ));

                        $total++;
                        $tableFindings++;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Quote identifier safely for SQLite: "name"
     */
    private function qi(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $name = str_replace('"', '""', $name);
            return '"' . $name . '"';
        }
        return '"' . $name . '"';
    }
}
