<?php

return [

    'integrity' => [
        'corrupted' => 'Перевірка цілісності бази не пройдена: :detail',
    ],

    'foreign_keys' => [
        'orphan_row' => 'Рядок у :table посилається через :column на неіснуючий запис у :parent',
    ],

    'type_mismatch' => [
        'column' => ':table.:column оголошено числовим, але в :count рядку(-ах) лежить текст',
    ],

    'empty_values' => [
        'not_null_but_blank' => ':table.:column має NOT NULL, але порожній у :count рядку(-ах)',
        'mostly_empty' => ':table.:column порожній у :percent% рядків',
    ],

    'duplicate_rows' => [
        'group' => 'У :table є :count однакових рядків (без урахування первинного ключа)',
    ],

    'missing_primary_key' => [
        'table' => 'У :table немає первинного ключа',
    ],

    'unindexed_foreign_key' => [
        'column' => ':table.:column посилається на :parent, але не має індексу',
    ],

    'constant_column' => [
        'column' => 'У :table.:column однакове значення в усіх рядках',
    ],

    'empty_table' => [
        'table' => 'Таблиця :table порожня',
    ],

    'http_errors' => [
        'response' => 'HTTP :code :reason на :endpoint',
    ],

    'content_keywords' => [
        'match' => '«:issue» зафіксовано в :table.:column',
    ],

];
