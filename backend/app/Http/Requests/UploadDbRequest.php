<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.(int) config('db_audit.limits.max_upload_kb', 204800),
                'mimes:db,sqlite,sqlite3,zip,sql',
            ],
            'profile' => ['nullable', 'string', Rule::in(array_keys((array) config('db_audit.profiles', [])))],
        ];
    }

    public function profile(): string
    {
        return $this->input('profile') ?: (string) config('db_audit.default_profile', 'standard');
    }
}
