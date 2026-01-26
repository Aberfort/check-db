<?php

namespace App\Domain\DbAudit\Adapters;

use App\Domain\DbAudit\Contracts\DbAdapter;
use PDO;

class SqliteAdapter implements DbAdapter
{
    public function __construct(private PDO $pdo) {}

    public function listTables(): array
    {
        $rows = $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        return array_map(fn($r) => (string) $r['name'], $rows);
    }

    public function listColumns(string $table): array
    {
        $t = str_replace('"', '""', $table);
        $rows = $this->query('PRAGMA table_info("' . $t . '")');

        return array_map(function ($r) {
            return [
                'name' => (string) $r['name'],
                'type' => (string) ($r['type'] ?? ''),
                'notnull' => (int) ($r['notnull'] ?? 0),
                'pk' => (int) ($r['pk'] ?? 0),
                'default' => $r['dflt_value'] ?? null,
            ];
        }, $rows);
    }

    public function tableExists(string $table): bool
    {
        $rows = $this->query(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name = :n LIMIT 1",
            [':n' => $table]
        );

        return !empty($rows);
    }

    public function count(string $table): int
    {
        $t = str_replace('"', '""', $table);
        return (int) $this->scalar('SELECT COUNT(*) FROM "' . $t . '"');
    }

    public function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($bindings as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        $rows = $this->query($sql, $bindings);
        if (!$rows) return null;
        $first = array_values($rows[0]);
        return $first[0] ?? null;
    }

    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
