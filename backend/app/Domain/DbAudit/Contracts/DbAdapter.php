<?php

namespace App\Domain\DbAudit\Contracts;

interface DbAdapter
{
    /** @return string[] */
    public function listTables(): array;

    /** @return array<int, array{name:string,type:string,notnull:int,pk:int,default:mixed}> */
    public function listColumns(string $table): array;

    /** @return array<int, array{column:string,parentTable:string,parentColumn:?string}> */
    public function foreignKeys(string $table): array;

    /** @return array<int, array{name:string,unique:bool,columns:string[]}> */
    public function indexes(string $table): array;

    public function tableExists(string $table): bool;

    public function count(string $table): int;

    /** @return array<int, array<string,mixed>> */
    public function query(string $sql, array $bindings = []): array;

    public function scalar(string $sql, array $bindings = []): mixed;

    /** Quote an identifier that may have come from an untrusted schema. */
    public function quoteIdent(string $name): string;
}
