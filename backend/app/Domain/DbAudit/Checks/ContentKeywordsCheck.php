<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * Part of the optional crawl-export preset: crawlers record their own diagnostics
 * as literal strings ("H1 not found"), so those columns are scanned for them.
 */
class ContentKeywordsCheck extends BaseCheck
{
    public function key(): string
    {
        return 'content_keywords';
    }

    public function weight(): float
    {
        return 1.5;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();

        $config = (array) config('db_audit.content_keywords', []);
        $needles = array_values(array_filter((array) ($config['needles'] ?? []), 'is_string'));
        $wanted = array_values(array_filter((array) ($config['columns'] ?? []), 'is_string'));

        if ($needles === [] || $wanted === []) {
            return $res->skip();
        }

        $scannedAnything = false;

        $this->forEachTable($db, function (string $table) use ($db, $res, $needles, $wanted, &$scannedAnything): void {
            if ($res->isAtLimit()) {
                return;
            }

            $available = array_column($db->listColumns($table), 'name');
            $columns = array_values(array_intersect($wanted, $available));

            if ($columns === []) {
                return;
            }

            $scannedAnything = true;
            $hasEndpoint = in_array('endpoint', $available, true);

            foreach ($columns as $column) {
                foreach ($needles as $needle) {
                    if ($res->isAtLimit()) {
                        return;
                    }

                    $select = $hasEndpoint ? '"endpoint" AS endpoint, ' : '';

                    $rows = $db->query(
                        'SELECT '.$select.$db->quoteIdent($column).' AS value FROM '.$db->quoteIdent($table)
                        .' WHERE CAST('.$db->quoteIdent($column).' AS TEXT) LIKE :needle LIMIT 50',
                        [':needle' => '%'.$needle.'%']
                    );

                    foreach ($rows as $row) {
                        $endpoint = trim((string) ($row['endpoint'] ?? ''));

                        $res->add(new CheckFinding(
                            severity: Severity::WARNING,
                            messageKey: 'content_keywords.match',
                            params: ['issue' => $needle, 'table' => $table, 'column' => $column],
                            table: $table,
                            column: $column,
                            rowRef: $endpoint !== '' ? ['endpoint' => $endpoint] : null,
                            meta: ['needle' => $needle, 'endpoint' => $endpoint ?: null],
                            bucket: mb_substr($needle, 0, 64),
                        ));
                    }
                }
            }
        });

        return $scannedAnything ? $res : $res->skip();
    }
}
