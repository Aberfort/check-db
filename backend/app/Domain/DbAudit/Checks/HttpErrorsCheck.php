<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * Part of the optional crawl-export preset: reads an `error_pages` table if the
 * uploaded database happens to have one, and skips silently otherwise.
 */
class HttpErrorsCheck extends BaseCheck
{
    public function key(): string
    {
        return 'http_errors';
    }

    public function weight(): float
    {
        return 2.0;
    }

    public function run(DbAdapter $db): CheckResult
    {
        $res = $this->result();

        if (! $db->tableExists('error_pages')) {
            return $res->skip();
        }

        $available = array_column($db->listColumns('error_pages'), 'name');
        if (! in_array('error_code', $available, true)) {
            return $res->skip();
        }

        $rows = $db->query(
            'SELECT * FROM "error_pages" WHERE "error_code" IS NOT NULL'
            . ' ORDER BY CAST("error_code" AS INTEGER) DESC LIMIT ' . $res->limit
        );

        foreach ($rows as $row) {
            $code = (int) ($row['error_code'] ?? 0);

            // 2xx rows are healthy pages; they are not findings.
            if ($code < 300) {
                continue;
            }

            $endpoint = trim((string) ($row['endpoint'] ?? ''));
            $reason = trim((string) ($row['error_reason'] ?? ''));

            $res->add(new CheckFinding(
                severity: $code >= 400 ? Severity::CRITICAL : Severity::WARNING,
                messageKey: 'http_errors.response',
                params: ['code' => $code, 'reason' => $reason, 'endpoint' => $endpoint],
                table: 'error_pages',
                column: 'error_code',
                rowRef: $endpoint !== '' ? ['endpoint' => $endpoint] : null,
                meta: [
                    'http_code' => $code,
                    'reason' => $reason ?: null,
                    'endpoint' => $endpoint ?: null,
                ],
                bucket: (string) $code,
            ));
        }

        return $res;
    }
}
