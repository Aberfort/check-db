<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class HttpErrorsCheck implements DbCheck
{
    public function key(): string { return 'http_errors'; }
    public function title(): string { return 'HTTP errors (error_pages)'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        $tables = $db->listTables();
        if (!in_array('error_pages', $tables, true)) {
            return $res;
        }

        // endpoint | error_code | error_reason | error_text
        $rows = $db->query("
    SELECT endpoint, error_code, error_reason, error_text
    FROM error_pages
    WHERE error_code IS NOT NULL
    ORDER BY CAST(error_code AS INTEGER) ASC
");

        foreach ($rows as $r) {
            $code = (int) ($r['error_code'] ?? 0);
            if ($code <= 0) continue;

            $reason = trim((string)($r['error_reason'] ?? ''));
            $endpoint = trim((string)($r['endpoint'] ?? ''));

            $sev = $this->severityFor($code);

            $text = (string)($r['error_text'] ?? '');
            $text = $this->truncate($text, 800);

            $msg = 'HTTP ' . $code . ($reason ? (' ' . $reason) : '');

            $res->add(new CheckFinding(
                severity: $sev,
                message: $msg,
                table: 'error_pages',
                column: 'error_code',
                rowRef: $endpoint ? ['endpoint' => $endpoint] : null,
                meta: [
                    'http_code' => $code,
                    'reason' => $reason ?: null,
                    'endpoint' => $endpoint ?: null,
                    'error_text' => $text ?: null,
                ],
            ));
        }

        return $res;
    }

    private function severityFor(int $code): string
    {
        if ($code >= 200 && $code <= 299) return 'ok';
        if ($code >= 300 && $code <= 399) return 'warning';
        return 'critical';
    }

    private function truncate(string $s, int $max): string
    {
        $s = trim($s);
        if ($s === '') return '';
        if (mb_strlen($s) <= $max) return $s;
        return mb_substr($s, 0, $max) . '…';
    }
}
