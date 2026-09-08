<?php

use App\Domain\DbAudit\Checks\ConstantColumnCheck;
use App\Domain\DbAudit\Checks\ContentKeywordsCheck;
use App\Domain\DbAudit\Checks\DuplicateRowsCheck;
use App\Domain\DbAudit\Checks\EmptyTableCheck;
use App\Domain\DbAudit\Checks\EmptyValuesCheck;
use App\Domain\DbAudit\Checks\ForeignKeyViolationsCheck;
use App\Domain\DbAudit\Checks\HttpErrorsCheck;
use App\Domain\DbAudit\Checks\IntegrityCheck;
use App\Domain\DbAudit\Checks\MissingPrimaryKeyCheck;
use App\Domain\DbAudit\Checks\TypeMismatchCheck;
use App\Domain\DbAudit\Checks\UnindexedForeignKeyCheck;

return [

    /*
     * Every check works off the uploaded database's own schema, so the audit
     * makes sense for any SQLite file rather than one particular application's.
     */
    'checks' => [
        'integrity' => IntegrityCheck::class,
        'foreign_keys' => ForeignKeyViolationsCheck::class,
        'type_mismatch' => TypeMismatchCheck::class,
        'empty_values' => EmptyValuesCheck::class,
        'duplicate_rows' => DuplicateRowsCheck::class,
        'missing_primary_key' => MissingPrimaryKeyCheck::class,
        'unindexed_foreign_key' => UnindexedForeignKeyCheck::class,
        'constant_column' => ConstantColumnCheck::class,
        'empty_table' => EmptyTableCheck::class,

        // Crawl-export preset: these skip themselves unless the schema matches.
        'http_errors' => HttpErrorsCheck::class,
        'content_keywords' => ContentKeywordsCheck::class,
    ],

    'default_profile' => 'standard',

    'profiles' => [
        'quick' => [
            'checks' => ['integrity', 'foreign_keys', 'type_mismatch'],
        ],

        'standard' => [
            'checks' => [
                'integrity',
                'foreign_keys',
                'type_mismatch',
                'empty_values',
                'duplicate_rows',
                'missing_primary_key',
                'empty_table',
            ],
        ],

        'thorough' => [
            'checks' => [
                'integrity',
                'foreign_keys',
                'type_mismatch',
                'empty_values',
                'duplicate_rows',
                'missing_primary_key',
                'unindexed_foreign_key',
                'constant_column',
                'empty_table',
            ],
        ],

        'crawl' => [
            'checks' => [
                'integrity',
                'foreign_keys',
                'type_mismatch',
                'empty_values',
                'duplicate_rows',
                'http_errors',
                'content_keywords',
            ],
        ],
    ],

    'limits' => [
        // Largest accepted upload, in kilobytes.
        'max_upload_kb' => (int) env('DB_AUDIT_MAX_UPLOAD_KB', 204800),

        // Keeps one pathological table from producing millions of rows.
        'findings_per_check' => 500,

        // A nullable column empty for at least this share of rows is reported.
        'empty_ratio_threshold' => 0.9,

        // Below this row count a single distinct value is not yet meaningful.
        'constant_column_min_rows' => 5,
    ],

    'content_keywords' => [
        'columns' => ['h1', 'description', 'headings_hierarchy'],

        'needles' => [
            'H1 not found',
            'H1 is missing',
            'Description not found',
            'no headings found',
            'multiple H1 tags found',
            'H1 is not first, starts with H2',
            'H1 is not first, starts with H3',
            'H1 is not first, starts with H4',
        ],
    ],

    'cleanup' => [
        'ttl_days' => 14,
        'only_finished' => true,
        'limit' => 200,
    ],
];
