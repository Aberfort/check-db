<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    protected $fillable = [
        'status',
        'progress',
        'original_name',
        'stored_path',
        'db_type',
        'summary',
        'error_message',
        'score',
    ];

    protected $casts = [
        'summary' => 'array',
    ];
}
