<?php

namespace Tests\Unit\Checks;

use App\Domain\DbAudit\Checks\EmptyValuesCheck;
use App\Domain\DbAudit\Checks\ForeignKeyViolationsCheck;
use App\Domain\DbAudit\Checks\IntegrityCheck;
use App\Domain\DbAudit\Checks\TypeMismatchCheck;
use App\Domain\DbAudit\DTO\Severity;
use Tests\Support\BuildsSqliteFixtures;
use Tests\TestCase;

class DataChecksTest extends TestCase
{
    use BuildsSqliteFixtures;

    public function test_it_passes_integrity_on_a_healthy_file(): void
    {
        $db = $this->fixture('CREATE TABLE t (id INTEGER PRIMARY KEY)');

        $this->assertTrue((new IntegrityCheck)->run($db)->passed());
    }

    public function test_it_finds_rows_pointing_at_a_missing_parent(): void
    {
        $db = $this->fixture(
            'CREATE TABLE customers (id INTEGER PRIMARY KEY)',
            'CREATE TABLE orders (id INTEGER PRIMARY KEY, customer_id INTEGER REFERENCES customers(id))',
            'INSERT INTO customers (id) VALUES (1)',
            'INSERT INTO orders (id, customer_id) VALUES (1, 1), (2, 999)',
        );

        $result = (new ForeignKeyViolationsCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame(Severity::CRITICAL, $result->findings[0]->severity);
        $this->assertSame('orders', $result->findings[0]->table);
        $this->assertSame('customer_id', $result->findings[0]->column);
    }

    public function test_it_finds_text_stored_in_a_numeric_column(): void
    {
        $db = $this->fixture(
            'CREATE TABLE orders (id INTEGER PRIMARY KEY, total REAL)',
            "INSERT INTO orders (total) VALUES (10.5), ('pending'), (7.25)",
        );

        $result = (new TypeMismatchCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame('total', $result->findings[0]->column);
        $this->assertSame(1, $result->findings[0]->meta['affected_rows']);
        $this->assertSame('pending', $result->findings[0]->meta['sample_value']);
    }

    /**
     * DATETIME resolves to NUMERIC affinity, and storing dates there as text is
     * the normal way to use SQLite — flagging it would bury real findings.
     */
    public function test_it_does_not_flag_text_dates_in_datetime_columns(): void
    {
        $db = $this->fixture(
            'CREATE TABLE events (id INTEGER PRIMARY KEY, happened_at DATETIME)',
            "INSERT INTO events (happened_at) VALUES ('2026-01-01 10:00:00')",
        );

        $this->assertSame([], (new TypeMismatchCheck)->run($db)->findings);
    }

    public function test_it_reports_a_not_null_column_filled_with_blanks(): void
    {
        $db = $this->fixture(
            'CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT NOT NULL)',
            "INSERT INTO users (email) VALUES ('a@example.test'), (''), ('   ')",
        );

        $result = (new EmptyValuesCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame('empty_values.not_null_but_blank', $result->findings[0]->messageKey);
        $this->assertSame(2, $result->findings[0]->meta['affected_rows']);
    }

    public function test_it_reports_a_nullable_column_that_was_never_populated(): void
    {
        $db = $this->fixture(
            'CREATE TABLE products (id INTEGER PRIMARY KEY, description TEXT)',
            "INSERT INTO products (description) VALUES ('written'), (NULL), (NULL), (NULL),"
                .' (NULL), (NULL), (NULL), (NULL), (NULL), (NULL)',
        );

        $result = (new EmptyValuesCheck)->run($db);

        $this->assertCount(1, $result->findings);
        $this->assertSame('empty_values.mostly_empty', $result->findings[0]->messageKey);
        $this->assertSame(90, $result->findings[0]->params['percent']);
    }

    public function test_it_leaves_a_well_populated_column_alone(): void
    {
        $db = $this->fixture(
            'CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT)',
            "INSERT INTO products (name) VALUES ('a'), ('b'), ('c')",
        );

        $this->assertSame([], (new EmptyValuesCheck)->run($db)->findings);
    }
}
