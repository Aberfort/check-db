<?php

return [

    'integrity' => [
        'title' => 'Storage integrity',
        'description' => 'Runs SQLite\'s own integrity check to detect a corrupted or truncated file.',
    ],

    'foreign_keys' => [
        'title' => 'Foreign key violations',
        'description' => 'Finds rows pointing at parent records that no longer exist, using the foreign keys declared in the file.',
    ],

    'type_mismatch' => [
        'title' => 'Type mismatches',
        'description' => 'SQLite accepts text in a numeric column. These values break comparisons and arithmetic later on.',
    ],

    'empty_values' => [
        'title' => 'Empty values',
        'description' => 'NOT NULL columns filled with blanks, and nullable columns that were never populated.',
    ],

    'duplicate_rows' => [
        'title' => 'Duplicate rows',
        'description' => 'Rows that are identical once the primary key is ignored — the same record stored twice.',
    ],

    'missing_primary_key' => [
        'title' => 'Missing primary keys',
        'description' => 'Tables without a primary key allow silent duplicates and cannot be updated row by row.',
    ],

    'unindexed_foreign_key' => [
        'title' => 'Unindexed foreign keys',
        'description' => 'SQLite never indexes the child side of a foreign key, so joins and parent deletes become full scans.',
    ],

    'constant_column' => [
        'title' => 'Constant columns',
        'description' => 'Columns holding one value in every row carry no information and are usually dead fields.',
    ],

    'empty_table' => [
        'title' => 'Empty tables',
        'description' => 'Tables defined in the schema but never populated.',
    ],

    'http_errors' => [
        'title' => 'HTTP errors',
        'description' => 'Failing responses recorded by a crawl export. Only runs when an error_pages table is present.',
    ],

    'content_keywords' => [
        'title' => 'Content issues',
        'description' => 'Diagnostics a crawler wrote into the data, such as a missing H1 or description.',
    ],

];
