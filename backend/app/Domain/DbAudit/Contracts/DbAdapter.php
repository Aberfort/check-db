<?php

namespace App\Domain\DbAudit\Contracts;

interface DbAdapter
{
    public function listTables(): array;

    /** @return array<int, array{name:string,type:string,notnull:int,pk:int,default:mixed}> */
    public function listColumns(string $table): array;

    public function tableExists(string $table): bool;

    public function count(string $table): int;

    /** @return array<int, array<string,mixed>> */
    public function query(string $sql, array $bindings = []): array;

    public function scalar(string $sql, array $bindings = []): mixed;
}
