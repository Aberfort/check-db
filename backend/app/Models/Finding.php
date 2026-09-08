<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finding extends Model
{
    protected $fillable = [
        'analysis_id',
        'check_key',
        'severity',
        'table_name',
        'column_name',
        'row_ref',
        'message_key',
        'message_params',
        'bucket',
        'meta',
    ];

    protected $casts = [
        'row_ref' => 'array',
        'message_params' => 'array',
        'meta' => 'array',
    ];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    /** Render this finding in the requested locale. */
    public function message(?string $locale = null): string
    {
        return (string) __('findings.'.$this->message_key, $this->message_params ?? [], $locale);
    }
}
