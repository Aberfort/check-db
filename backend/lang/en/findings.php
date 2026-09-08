<?php

return [

    'integrity' => [
        'corrupted' => 'Database integrity check failed: :detail',
    ],

    'foreign_keys' => [
        'orphan_row' => 'Row in :table references a missing :parent record via :column',
    ],

    'type_mismatch' => [
        'column' => ':table.:column is declared numeric but holds text in :count row(s)',
    ],

    'empty_values' => [
        'not_null_but_blank' => ':table.:column is NOT NULL yet blank in :count row(s)',
        'mostly_empty' => ':table.:column is empty in :percent% of rows',
    ],

    'duplicate_rows' => [
        'group' => ':table contains :count identical rows (primary key aside)',
    ],

    'missing_primary_key' => [
        'table' => ':table has no primary key',
    ],

    'unindexed_foreign_key' => [
        'column' => ':table.:column references :parent but is not indexed',
    ],

    'constant_column' => [
        'column' => ':table.:column holds the same value in every row',
    ],

    'empty_table' => [
        'table' => ':table is empty',
    ],

    'http_errors' => [
        'response' => 'HTTP :code :reason at :endpoint',
    ],

    'content_keywords' => [
        'match' => '":issue" recorded in :table.:column',
    ],

];
