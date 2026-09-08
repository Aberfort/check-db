<?php

namespace Tests\Support;

use App\Domain\DbAudit\Adapters\SqliteAdapter;
use PDO;

trait BuildsSqliteFixtures
{
    /** Keeps the connection referenced so the in-memory database stays alive. */
    private ?PDO $fixturePdo = null;

    protected function fixture(string ...$statements): SqliteAdapter
    {
        $this->fixturePdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        foreach ($statements as $sql) {
            $this->fixturePdo->exec($sql);
        }

        return new SqliteAdapter($this->fixturePdo);
    }

    /** @return string[] rendered messages, for asserting on content */
    protected function messagesOf(\App\Domain\DbAudit\DTO\CheckResult $result): array
    {
        return array_map(
            fn ($f) => (string) __('findings.'.$f->messageKey, $f->params),
            $result->findings
        );
    }
}
