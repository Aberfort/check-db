<?php

namespace Tests\Unit\Checks;

use App\Domain\DbAudit\Checks\ConstantColumnCheck;
use App\Domain\DbAudit\Checks\DuplicateRowsCheck;
use App\Domain\DbAudit\Checks\EmptyTableCheck;
use App\Domain\DbAudit\Checks\MissingPrimaryKeyCheck;
use App\Domain\DbAudit\Checks\UnindexedForeignKeyCheck;
use App\Domain\DbAudit\DTO\Severity;
use Tests\Support\BuildsSqliteFixtures;
use Tests\TestCase;

class SchemaChecksTest extends TestCase
{
    use BuildsSqliteFixtures;

    public function test_it_reports_a_table_without_a_primary_key(): void
    {
        $db = $this->fixture(
            'CREATE TABLE with_pk (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE without_pk (name TEXT, value TEXT)',
        );

        $result = (new MissingPrimaryKeyCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame('without_pk', $result->findings[0]->table);
        $this->assertSame(Severity::WARNING, $result->findings[0]->severity);
    }

    public function test_it_reports_a_foreign_key_column_that_has_no_index(): void
    {
        $db = $this->fixture(
            'CREATE TABLE parents (id INTEGER PRIMARY KEY)',
            'CREATE TABLE indexed_child (id INTEGER PRIMARY KEY, parent_id INTEGER REFERENCES parents(id))',
            'CREATE INDEX idx_parent ON indexed_child (parent_id)',
            'CREATE TABLE bare_child (id INTEGER PRIMARY KEY, parent_id INTEGER REFERENCES parents(id))',
        );

        $result = (new UnindexedForeignKeyCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame('bare_child', $result->findings[0]->table);
        $this->assertSame('parent_id', $result->findings[0]->column);
    }

    public function test_it_skips_when_the_schema_declares_no_foreign_keys(): void
    {
        $db = $this->fixture('CREATE TABLE solo (id INTEGER PRIMARY KEY)');

        $result = (new UnindexedForeignKeyCheck)->run($db);

        $this->assertTrue($result->skipped);
        $this->assertSame([], $result->findings);
    }

    public function test_it_reports_rows_that_are_identical_apart_from_the_primary_key(): void
    {
        $db = $this->fixture(
            'CREATE TABLE items (id INTEGER PRIMARY KEY, sku TEXT, qty INTEGER)',
            "INSERT INTO items (sku, qty) VALUES ('A', 1), ('A', 1), ('B', 2)",
        );

        $result = (new DuplicateRowsCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame(2, $result->findings[0]->meta['occurrences']);
    }

    public function test_it_reports_a_column_holding_one_value_everywhere(): void
    {
        $db = $this->fixture(
            'CREATE TABLE flags (id INTEGER PRIMARY KEY, is_active INTEGER, label TEXT)',
            "INSERT INTO flags (is_active, label) VALUES (1,'a'),(1,'b'),(1,'c'),(1,'d'),(1,'e')",
        );

        $result = (new ConstantColumnCheck)->run($db);

        $columns = array_map(fn ($f) => $f->column, $result->findings);
        $this->assertSame(['is_active'], $columns);
    }

    public function test_it_ignores_constant_columns_in_tables_too_small_to_judge(): void
    {
        $db = $this->fixture(
            'CREATE TABLE tiny (id INTEGER PRIMARY KEY, flag INTEGER)',
            'INSERT INTO tiny (flag) VALUES (1),(1)',
        );

        $this->assertSame([], (new ConstantColumnCheck)->run($db)->findings);
    }

    public function test_it_reports_empty_tables_as_informational_only(): void
    {
        $db = $this->fixture(
            'CREATE TABLE filled (id INTEGER PRIMARY KEY)',
            'INSERT INTO filled (id) VALUES (1)',
            'CREATE TABLE blank (id INTEGER PRIMARY KEY)',
        );

        $result = (new EmptyTableCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame('blank', $result->findings[0]->table);
        $this->assertSame(Severity::INFO, $result->findings[0]->severity);
    }
}
