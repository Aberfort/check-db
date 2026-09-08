<?php

namespace App\Domain\DbAudit\Services;

use App\Domain\DbAudit\Contracts\DbCheck;

class CheckFactory
{
    /** @return array<string, DbCheck> keyed by check key */
    public function forProfile(?string $profile): array
    {
        $profiles = (array) config('db_audit.profiles', []);
        $name = $profile !== null && isset($profiles[$profile])
            ? $profile
            : (string) config('db_audit.default_profile', 'standard');

        return $this->byKeys((array) ($profiles[$name]['checks'] ?? []));
    }

    /**
     * @param  string[]  $keys
     * @return array<string, DbCheck>
     */
    public function byKeys(array $keys): array
    {
        $registry = (array) config('db_audit.checks', []);

        $out = [];
        foreach ($keys as $key) {
            if (isset($registry[$key])) {
                $out[$key] = app($registry[$key]);
            }
        }

        return $out;
    }

    /** @return string[] */
    public function profileNames(): array
    {
        return array_keys((array) config('db_audit.profiles', []));
    }
}
