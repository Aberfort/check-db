<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

/**
 * Builds the database offered as "try a sample" in the UI. Every flaw below is
 * deliberate: each one is what a specific check is meant to catch.
 */
class MakeSampleDatabaseCommand extends Command
{
    protected $signature = 'db:make-sample {--path= : Where to write the file}';

    protected $description = 'Generate the bundled sample SQLite database';

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('samples/shop-demo.sqlite');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        if (is_file($path)) {
            unlink($path);
        }

        $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $this->schema($pdo);
        $this->seed($pdo);

        $this->info('Sample database written to '.$path);

        return self::SUCCESS;
    }

    private function schema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE customers (
                id INTEGER PRIMARY KEY,
                email TEXT NOT NULL,
                full_name TEXT,
                country TEXT,
                newsletter_opt_in INTEGER
            )
        ');

        // orders.customer_id is a foreign key with no index on the child side.
        $pdo->exec('
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY,
                customer_id INTEGER REFERENCES customers(id),
                placed_at TEXT,
                total REAL,
                status TEXT
            )
        ');

        // No primary key, so duplicate lines cannot be told apart.
        $pdo->exec('
            CREATE TABLE order_items (
                order_id INTEGER REFERENCES orders(id),
                sku TEXT,
                quantity INTEGER,
                unit_price REAL
            )
        ');

        $pdo->exec('
            CREATE TABLE products (
                id INTEGER PRIMARY KEY,
                sku TEXT UNIQUE,
                name TEXT,
                description TEXT,
                is_active INTEGER
            )
        ');

        $pdo->exec('
            CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY,
                actor TEXT,
                action TEXT,
                created_at TEXT
            )
        ');
    }

    private function seed(PDO $pdo): void
    {
        $customers = $pdo->prepare(
            'INSERT INTO customers (id, email, full_name, country, newsletter_opt_in) VALUES (?, ?, ?, ?, ?)'
        );

        $countries = ['UA', 'PL', 'DE', 'US', 'FR'];

        for ($i = 1; $i <= 40; $i++) {
            $customers->execute([
                $i,
                // A NOT NULL column defeated by empty strings.
                in_array($i, [7, 19, 33], true) ? '' : "customer{$i}@example.test",
                "Customer {$i}",
                $countries[$i % 5],
                0,
            ]);
        }

        $orders = $pdo->prepare(
            'INSERT INTO orders (id, customer_id, placed_at, total, status) VALUES (?, ?, ?, ?, ?)'
        );

        for ($i = 1; $i <= 120; $i++) {
            // Customers 900+ were never inserted, leaving orphan orders behind.
            $customerId = $i % 17 === 0 ? 900 + $i : (($i % 40) + 1);

            $orders->execute([
                $i,
                $customerId,
                sprintf('2026-%02d-%02d 10:00:00', ($i % 12) + 1, ($i % 27) + 1),
                // A REAL column holding text, which breaks any later SUM().
                $i % 23 === 0 ? 'pending' : round(15 + ($i * 3.75), 2),
                $i % 5 === 0 ? 'refunded' : 'paid',
            ]);
        }

        $items = $pdo->prepare(
            'INSERT INTO order_items (order_id, sku, quantity, unit_price) VALUES (?, ?, ?, ?)'
        );

        for ($i = 1; $i <= 120; $i++) {
            $items->execute([$i, 'SKU-'.(($i % 12) + 1), 1 + ($i % 3), 9.99]);

            // The same line stored twice, indistinguishable without a key.
            if ($i % 9 === 0) {
                $items->execute([$i, 'SKU-'.(($i % 12) + 1), 1 + ($i % 3), 9.99]);
            }
        }

        $products = $pdo->prepare(
            'INSERT INTO products (id, sku, name, description, is_active) VALUES (?, ?, ?, ?, ?)'
        );

        for ($i = 1; $i <= 12; $i++) {
            $products->execute([
                $i,
                'SKU-'.$i,
                'Product '.$i,
                // Descriptions were never written for almost the whole catalogue.
                $i === 1 ? 'The only product anyone described.' : null,
                // A flag nobody ever flipped.
                1,
            ]);
        }

        // audit_log stays empty on purpose.
    }
}
