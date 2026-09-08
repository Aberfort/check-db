<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analysis extends Model
{
    protected $fillable = [
        'status',
        'progress',
        'original_name',
        'stored_path',
        'db_type',
        'profile',
        'summary',
        'error_message',
        'score',
    ];

    protected $casts = [
        'summary' => 'array',
        'progress' => 'integer',
        'score' => 'integer',
    ];

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['success', 'error'], true);
    }
}
