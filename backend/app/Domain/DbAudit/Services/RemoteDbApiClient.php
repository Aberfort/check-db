<?php

namespace App\Domain\DbAudit\Services;

use RuntimeException;

class RemoteDbApiClient
{
    public function __construct(private RemoteSettingsStore $store) {}

    /** @return array<int, array<string,mixed>> */
    public function list(): array
    {
        $cfg = $this->store->get();
        if (empty($cfg['enabled'])) {
            throw new RuntimeException('Remote API вимкнено в налаштуваннях.');
        }

        $base = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $path = (string)($cfg['list_path'] ?? '/api/dbs');
        if ($base === '') {
            throw new RuntimeException('Не задано base_url.');
        }

        $url = $base . '/' . ltrim($path, '/');

        $headers = $this->buildHeaders($cfg);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $this->headersToString($headers),
                'timeout' => 60,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            throw new RuntimeException('Не вдалося отримати список баз з remote API.');
        }

        $data = json_decode($raw, true);

        if (is_array($data) && isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }
        if (is_array($data)) {
            // якщо це список
            $isList = array_is_list($data);
            if ($isList) return $data;
        }

        throw new RuntimeException('Remote API повернув неочікуваний формат.');
    }

    /** @return array{url:string, name?:string} */
    public function buildDownloadUrl(string $id): array
    {
        $cfg = $this->store->get();
        if (empty($cfg['enabled'])) {
            throw new RuntimeException('Remote API вимкнено.');
        }

        $base = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $tpl = (string)($cfg['download_path'] ?? '/api/dbs/{id}/download');
        if ($base === '') throw new RuntimeException('Не задано base_url.');

        $path = str_replace('{id}', rawurlencode($id), $tpl);
        $url = $base . '/' . ltrim($path, '/');

        return ['url' => $url];
    }

    /** @param array<string,mixed> $cfg */
    private function buildHeaders(array $cfg): array
    {
        // Bearer має пріоритет
        if (!empty($cfg['bearer'])) {
            return ['Authorization' => 'Bearer ' . (string)$cfg['bearer']];
        }

        if (!empty($cfg['basic_user'])) {
            $u = (string)$cfg['basic_user'];
            $p = (string)($cfg['basic_pass'] ?? '');
            return ['Authorization' => 'Basic ' . base64_encode($u . ':' . $p)];
        }

        return [];
    }

    /** @param array<string,string> $headers */
    private function headersToString(array $headers): string
    {
        $out = [];
        foreach ($headers as $k => $v) {
            $out[] = $k . ': ' . $v;
        }
        return implode("\r\n", $out);
    }
}
