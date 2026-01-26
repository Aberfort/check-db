<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;

class OrphanRowsCheck implements DbCheck
{
    public function key(): string { return 'orphan_rows'; }
    public function title(): string { return 'Orphan rows (немає parent)'; }

    public function run(DbAdapter $db): CheckResult
    {
        $res = new CheckResult($this->key(), $this->title());

        $rels = config('db_audit.relations', []);
        foreach ($rels as $rel) {
            $ct = $rel['child_table'] ?? null;
            $cc = $rel['child_col'] ?? null;
            $pt = $rel['parent_table'] ?? null;
            $pc = $rel['parent_col'] ?? null;
            $sev = $rel['severity'] ?? 'warning';

            if (!$ct || !$cc || !$pt || !$pc) continue;
            if (!$db->tableExists($ct) || !$db->tableExists($pt)) continue;

            $ctQ = str_replace('"','""',$ct);
            $ccQ = str_replace('"','""',$cc);
            $ptQ = str_replace('"','""',$pt);
            $pcQ = str_replace('"','""',$pc);

            // child rows where child.cc not null and parent missing
            $sql = '
                SELECT c.rowid AS __rowid, c."' . $ccQ . '" AS ref
                FROM "' . $ctQ . '" c
                LEFT JOIN "' . $ptQ . '" p ON p."' . $pcQ . '" = c."' . $ccQ . '"
                WHERE c."' . $ccQ . '" IS NOT NULL AND p."' . $pcQ . '" IS NULL
                LIMIT 50
            ';

            $rows = $db->query($sql);

            foreach ($rows as $r) {
                $res->add(new CheckFinding(
                    severity: $sev,
                    message: "Orphan: {$ct}.{$cc} посилається на відсутній {$pt}.{$pc}",
                    table: $ct,
                    column: $cc,
                    rowRef: ['rowid' => (int) $r['__rowid'], 'ref' => $r['ref']],
                    meta: ['parent_table' => $pt, 'parent_col' => $pc],
                ));
            }
        }

        return $res;
    }
}
