<?php

use App\Domain\DbAudit\Checks\ContentKeywordsCheck;
use App\Domain\DbAudit\Checks\DuplicatesCheck;
use App\Domain\DbAudit\Checks\HttpErrorsCheck;
use App\Domain\DbAudit\Checks\IntegrityCheck;
use App\Domain\DbAudit\Checks\InvalidRefsCheck;
use App\Domain\DbAudit\Checks\MissingTablesCheck;
use App\Domain\DbAudit\Checks\OrphanRowsCheck;
use App\Domain\DbAudit\Checks\RequiredFieldsCheck;

return [
    // для MVP: які таблиці мають бути
    'required_tables'  => [
        'metadata',
        'error_pages',
    ],

    // унікальність (duplicates)
    'unique_rules'     => [
        'metadata' => ['key'],
    ],

    // orphan rules (child -> parent)
    'relations'        => [
        // ['child_table' => 'posts', 'child_col' => 'author_id', 'parent_table' => 'users', 'parent_col' => 'id', 'severity' => 'warning'],
    ],

    // null/empty критичні поля
    'required_fields'  => [
        'metadata' => ['key', 'value'],
    ],

    // Контентні ключові проблеми (MVP)
    'content_keywords' => [
        // шукаємо по всіх таблицях, але тільки в колонках з такими іменами (якщо є в таблиці)
        'columns'                => ['h1', 'description', 'headings_hierarchy'],

        // які строки вважати проблемою
        'needles'                => [
            'H1 not found',
            'Description not found',
            'H1 is not first, starts with H2',
            'no headings found',
            'H1 is missing',
            'H1 is not first, starts with H2',
            'H1 is not first, starts with H3',
            'H1 is not first, starts with H4',
            'multiple H1 tags found',
            'multiple H1 tags found (2)',
            'multiple H1 tags found (3)',
            'multiple H1 tags found (4)',
        ],

        // safety limits щоб не вбити UI/пам’ять на великих БД
        'max_findings_total'     => 5000,
        'max_findings_per_table' => 800,
    ],

    /**
     * Реєстр чеків (ключ -> клас)
     * Тут додаватимемо нові чеки.
     */
    'checks'           => [
        'missing_tables'   => MissingTablesCheck::class,
        'required_fields'  => RequiredFieldsCheck::class,
        'duplicates'       => DuplicatesCheck::class,
        'orphan_rows'      => OrphanRowsCheck::class,
        'invalid_refs'     => InvalidRefsCheck::class,
        'integrity_check'  => IntegrityCheck::class,
        'http_errors'      => HttpErrorsCheck::class,
        'content_keywords' => ContentKeywordsCheck::class,
    ],

    /**
     * Профілі запуску
     */
    'profiles'         => [
        'basic'  => [
            'title'  => 'Basic',
            'checks' => ['missing_tables', 'required_fields', 'http_errors', 'content_keywords'],
        ],
        'full'   => [
            'title'  => 'Full',
            'checks' => [
                'integrity_check',
                'missing_tables',
                'required_fields',
                'duplicates',
                'orphan_rows',
                'http_errors',
                'content_keywords',
            ],
        ],
        'strict' => [
            'title'  => 'Strict',
            'checks' => [
                'integrity_check',
                'missing_tables',
                'required_fields',
                'duplicates',
                'orphan_rows',
                'invalid_refs',
                'http_errors',
                'content_keywords',
            ],
        ],
    ],

    'cleanup' => [
        'ttl_days' => 14,

        'only_finished' => true,

        'limit' => 200,
    ],
];
