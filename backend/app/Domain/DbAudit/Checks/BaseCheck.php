<?php

namespace App\Domain\DbAudit\Checks;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckResult;
use Throwable;

abstract class BaseCheck implements DbCheck
{
    public function weight(): float
    {
        return 1.0;
    }

    protected function result(): CheckResult
    {
        return new CheckResult($this->key(), (int) config('db_audit.limits.findings_per_check', 500));
    }

    /**
     * Uploaded files can contain virtual tables, WITHOUT ROWID tables and other
     * shapes that make a given pragma or query fail. One odd table must not
     * abort the whole analysis, so per-table work is isolated.
     */
    protected function forEachTable(DbAdapter $db, callable $fn): void
    {
        foreach ($db->listTables() as $table) {
            try {
                $fn($table);
            } catch (Throwable) {
                continue;
            }
        }
    }

    /**
     * SQLite resolves a declared column type to one of five affinities.
     * @see https://sqlite.org/datatype3.html#determination_of_column_affinity
     */
    protected function affinityOf(string $declaredType): string
    {
        $t = strtoupper($declaredType);

        if ($t === '') {
            return 'BLOB';
        }

        if (str_contains($t, 'INT')) {
            return 'INTEGER';
        }

        if (str_contains($t, 'CHAR') || str_contains($t, 'CLOB') || str_contains($t, 'TEXT')) {
            return 'TEXT';
        }

        if (str_contains($t, 'BLOB')) {
            return 'BLOB';
        }

        if (str_contains($t, 'REAL') || str_contains($t, 'FLOA') || str_contains($t, 'DOUB')) {
            return 'REAL';
        }

        return 'NUMERIC';
    }
}
