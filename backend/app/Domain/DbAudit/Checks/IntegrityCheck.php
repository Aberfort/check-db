<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class IntegrityCheck implements DbCheck
{
    public function key(): string { return 'integrity_check'; }
    public function title(): string { return 'DB integrity (corruption)'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        $rows = $db->query('PRAGMA integrity_check;');

        $messages = [];
        foreach ($rows as $r) {
            $val = null;

            if (is_array($r)) {
                $val = $r['integrity_check'] ?? (count($r) ? array_values($r)[0] : null);
            }

            $val = is_string($val) ? trim($val) : null;
            if ($val !== null && $val !== '' && strtolower($val) !== 'ok') {
                $messages[] = $val;
            }
        }

        if (!empty($messages)) {
            $res->add(new CheckFinding(
                severity: 'critical',
                message: 'Integrity check failed: база може бути пошкоджена',
                table: null,
                column: null,
                rowRef: null,
                meta: ['details' => $messages],
            ));
        }

        return $res;
    }
}
