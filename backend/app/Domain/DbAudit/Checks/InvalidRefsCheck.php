<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class InvalidRefsCheck implements DbCheck
{
    public function key(): string { return 'invalid_refs'; }
    public function title(): string { return 'Invalid refs (0/empty/bad)'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        $rels = config('db_audit.relations', []);
        foreach ($rels as $rel) {
            $ct = $rel['child_table'] ?? null;
            $cc = $rel['child_col'] ?? null;
            if (!$ct || !$cc) continue;
            if (!$db->tableExists($ct)) continue;

            $ctQ = str_replace('"','""',$ct);
            $ccQ = str_replace('"','""',$cc);

            // “биті” значення ref: NULL / '' / '0' / 0
            $sql = '
                SELECT rowid AS __rowid, "' . $ccQ . '" AS ref
                FROM "' . $ctQ . '"
                WHERE "' . $ccQ . '" IS NULL
                   OR TRIM(CAST("' . $ccQ . '" AS TEXT)) = ""
                   OR TRIM(CAST("' . $ccQ . '" AS TEXT)) = "0"
                LIMIT 50
            ';

            $rows = $db->query($sql);
            foreach ($rows as $r) {
                $res->add(new CheckFinding(
                    severity: 'warning',
                    message: "Invalid ref у {$ct}.{$cc}",
                    table: $ct,
                    column: $cc,
                    rowRef: ['rowid' => (int) $r['__rowid']],
                    meta: ['ref' => $r['ref']],
                ));
            }
        }

        return $res;
    }
}
