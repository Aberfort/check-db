<?php

namespace App\Domain\DbAudit\Services;

use App\Domain\DbAudit\Contracts\DbCheck;

class CheckFactory
{
    /** @return DbCheck[] */
    public function all(): array
    {
        $map = config('db_audit.checks', []); // key => class

        $out = [];
        foreach ($map as $key => $class) {
            $check = app($class); // DI
            $out[] = $check;
        }

        return $out;
    }

    /** @return DbCheck[] */
    public function byKeys(array $keys): array
    {
        $map = config('db_audit.checks', []);
        $out = [];

        foreach ($keys as $k) {
            $class = $map[$k] ?? null;
            if (!$class) continue;
            $out[] = app($class);
        }

        return $out;
    }
}
