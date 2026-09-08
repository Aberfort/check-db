<?php

namespace App\Domain\DbAudit\Adapters;

use App\Domain\DbAudit\Contracts\DbAdapter;
use PDO;

class SqliteAdapter implements DbAdapter
{
    /** @var array<string, array<int, array<string,mixed>>> */
    private array $columnCache = [];

    public function __construct(private PDO $pdo) {}

    public function listTables(): array
    {
        $rows = $this->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        return array_map(static fn ($r) => (string) $r['name'], $rows);
    }

    public function listColumns(string $table): array
    {
        if (isset($this->columnCache[$table])) {
            return $this->columnCache[$table];
        }

        $rows = $this->query('PRAGMA table_info('.$this->quoteIdent($table).')');

        return $this->columnCache[$table] = array_map(static fn ($r) => [
            'name' => (string) $r['name'],
            'type' => (string) ($r['type'] ?? ''),
            'notnull' => (int) ($r['notnull'] ?? 0),
            'pk' => (int) ($r['pk'] ?? 0),
            'default' => $r['dflt_value'] ?? null,
        ], $rows);
    }

    public function foreignKeys(string $table): array
    {
        $rows = $this->query('PRAGMA foreign_key_list('.$this->quoteIdent($table).')');

        return array_map(static fn ($r) => [
            'column' => (string) ($r['from'] ?? ''),
            'parentTable' => (string) ($r['table'] ?? ''),
            // "to" is null when the FK targets the parent's primary key implicitly.
            'parentColumn' => isset($r['to']) ? (string) $r['to'] : null,
        ], $rows);
    }

    public function indexes(string $table): array
    {
        $list = $this->query('PRAGMA index_list('.$this->quoteIdent($table).')');

        $out = [];
        foreach ($list as $idx) {
            $name = (string) ($idx['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $info = $this->query('PRAGMA index_info('.$this->quoteIdent($name).')');

            $out[] = [
                'name' => $name,
                'unique' => (int) ($idx['unique'] ?? 0) === 1,
                'columns' => array_values(array_filter(array_map(
                    static fn ($r) => isset($r['name']) ? (string) $r['name'] : null,
                    $info
                ))),
            ];
        }

        return $out;
    }

    public function tableExists(string $table): bool
    {
        return $this->query(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :n LIMIT 1",
            [':n' => $table]
        ) !== [];
    }

    public function count(string $table): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM '.$this->quoteIdent($table));
    }

    public function query(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($bindings as $k => $v) {
            $stmt->bindValue(is_int($k) ? $k + 1 : $k, $v);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        $rows = $this->query($sql, $bindings);
        if ($rows === []) {
            return null;
        }

        return array_values($rows[0])[0] ?? null;
    }

    /**
     * Table and column names come from the uploaded file's own schema, so they are
     * untrusted input and must never be interpolated raw.
     */
    public function quoteIdent(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }
}
