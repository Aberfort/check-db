<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Finding */
class FindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'check_key' => $this->check_key,
            'severity' => $this->severity,
            'table' => $this->table_name,
            'column' => $this->column_name,
            'row_ref' => $this->row_ref,
            'bucket' => $this->bucket,
            // Rendered here so the stored finding stays language-neutral.
            'message' => $this->message(),
            'meta' => $this->meta,
        ];
    }
}
