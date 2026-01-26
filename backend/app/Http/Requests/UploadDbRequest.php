<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required','file','max:512000','mimes:db,sqlite,sqlite3,zip,sql'],
            'profile' => ['nullable','string','in:basic,full,strict'],
        ];
    }
}
