<?php

namespace App\Domain\DbAudit\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;

class RemoteSettingsStore
{
    private const KEY = 'remote_db_api';

    /** @return array{base_url?:string, list_path?:string, download_path?:string, bearer?:string, basic_user?:string, basic_pass?:string, enabled?:bool} */
    public function get(): array
    {
        $row = AppSetting::query()->where('key', self::KEY)->first();
        if (!$row || !$row->value) return ['enabled' => false];

        try {
            $json = Crypt::decryptString($row->value);
            $data = json_decode($json, true);
            return is_array($data) ? $data : ['enabled' => false];
        } catch (\Throwable) {
            return ['enabled' => false];
        }
    }

    /** @param array<string,mixed> $data */
    public function put(array $data): void
    {
        // мінімальна нормалізація
        $payload = [
            'enabled' => (bool)($data['enabled'] ?? true),

            'base_url' => trim((string)($data['base_url'] ?? '')),
            'list_path' => trim((string)($data['list_path'] ?? '/api/dbs')),
            'download_path' => trim((string)($data['download_path'] ?? '/api/dbs/{id}/download')),

            'bearer' => trim((string)($data['bearer'] ?? '')),

            'basic_user' => trim((string)($data['basic_user'] ?? '')),
            'basic_pass' => trim((string)($data['basic_pass'] ?? '')),
        ];

        $enc = Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $enc]
        );
    }
}
