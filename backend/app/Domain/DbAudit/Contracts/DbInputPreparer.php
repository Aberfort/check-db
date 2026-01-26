<?php

namespace App\Domain\DbAudit\Contracts;

use App\Models\Analysis;

interface DbInputPreparer
{
    /** @return array{path:string, cleanup_paths:array<int,string>} */
    public function prepare(Analysis $analysis, string $uploadedPath, string $originalName): array;
}
