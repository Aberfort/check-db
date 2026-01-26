<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Finding extends Model
{
    protected $fillable = [
        'analysis_id',
        'check_key',
        'severity',
        'table_name',
        'column_name',
        'row_ref',
        'message',
        'meta',
    ];

    protected $casts = [
        'row_ref' => 'array',
        'meta' => 'array',
    ];
}
