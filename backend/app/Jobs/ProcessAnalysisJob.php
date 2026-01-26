<?php

namespace App\Jobs;

use App\Domain\DbAudit\Adapters\SqliteAdapter;
use App\Domain\DbAudit\Contracts\DbInputPreparer;
use App\Domain\DbAudit\Services\AnalysisRunner;
use App\Domain\DbAudit\Services\CheckFactory;
use App\Domain\DbAudit\Services\RemoteSettingsStore;
use App\Models\Analysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PDO;
use Throwable;

class ProcessAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $analysisId) {}

    public function handle(): void
    {
        $analysis = Analysis::query()->findOrFail($this->analysisId);

        // щоб finally був безпечний
        $prepared = ['path' => null, 'cleanup_paths' => []];

        try {
            $analysis->update(['status' => 'processing', 'progress' => 5]);

            $disk = Storage::disk('local');

            $isRemote = ($analysis->ingest ?? null) === 'remote' || !empty($analysis->remote_url);

            if (!$isRemote) {
                $uploadedAbs = $analysis->stored_path ? $disk->path($analysis->stored_path) : null;
                if (!$uploadedAbs || !is_file($uploadedAbs)) {
                    throw new \RuntimeException('Файл БД не знайдено у storage.');
                }
            } else {
                if (empty($analysis->remote_url)) {
                    throw new \RuntimeException('Remote URL не задано.');
                }
            }

            /** @var DbInputPreparer $preparer */
            $preparer = app(DbInputPreparer::class);

            $headers = is_array($analysis->remote_headers) ? $analysis->remote_headers : [];
            if (!$headers) {
                /** @var RemoteSettingsStore $store */
                $store = app(RemoteSettingsStore::class);
                $cfg = $store->get();
                if (!empty($cfg['bearer'])) {
                    $headers = ['Authorization' => 'Bearer ' . (string) $cfg['bearer']];
                } elseif (!empty($cfg['basic_user'])) {
                    $headers = [
                        'Authorization' => 'Basic ' . base64_encode(
                                (string) $cfg['basic_user'] . ':' . (string) ($cfg['basic_pass'] ?? '')
                            ),
                    ];
                }
            }

            if ($isRemote && $headers && empty($analysis->remote_headers)) {
                $analysis->update(['remote_headers' => $headers]);
                $analysis->refresh();
            }

            $sourcePathOrUrl = $isRemote ? $analysis->remote_url : $analysis->stored_path;
            $sourceName = $analysis->original_name ?: ($isRemote ? 'remote.db' : null);

            $prepared = $preparer->prepare($analysis, $sourcePathOrUrl, $sourceName);

            // PDO треба абсолютний шлях
            $dbAbs = $disk->path($prepared['path']);
            if (!is_file($dbAbs)) {
                throw new \RuntimeException('Підготовлена БД не знайдена у storage.');
            }

            $pdo = new PDO('sqlite:' . $dbAbs, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $analysis->update(['progress' => 15]);

            $db = new SqliteAdapter($pdo);

            // tables + counts
            $tableNames = $db->listTables();
            $analysis->update(['progress' => 25]);

            $counts = [];
            foreach ($tableNames as $i => $t) {
                $counts[$t] = $db->count($t);

                $analysis->update([
                    'progress' => 25 + (int) floor((($i + 1) / max(1, count($tableNames))) * 15), // 25..40
                ]);
            }

            // checks
            $analysis->update(['progress' => 40]);

            $profile = $analysis->profile ?: 'basic';

            // профіль -> keys
            $keys = config("db_audit.profiles.$profile.checks");
            if (!is_array($keys) || empty($keys)) {
                $keys = config('db_audit.profiles.basic.checks', []);
            }

            /** @var CheckFactory $factory */
            $factory = app(CheckFactory::class);

            // в CheckFactory має бути byKeys()
            $checks = method_exists($factory, 'byKeys')
                ? $factory->byKeys($keys)
                : (method_exists($factory, 'makeAll') ? $factory->makeAll() : []);

            if (empty($checks)) {
                throw new \RuntimeException('Список checks порожній (перевір config/db_audit.php profiles/checks та CheckFactory).');
            }

            $runner = new AnalysisRunner();
            $checksSummary = $runner->run($analysis, $db, $checks);

            $analysis->update(['progress' => 95]);

            $totalRows = array_sum($counts);
            $totalChecks = (int) ($checksSummary['overview']['total_checks'] ?? 0);

            $sev = $checksSummary['severity'] ?? ['ok' => 0, 'warning' => 0, 'critical' => 0];

            $warn = (int) ($sev['warning'] ?? 0);
            $crit = (int) ($sev['critical'] ?? 0);

            $totalIssues = $warn + $crit;

            $okRows = max(0, $totalRows - $totalIssues);

            // Weighted health score by % of rows with issues
            $weightedIssues = ($crit * 1.0) + ($warn * 0.5);
            $weightedPct = ($weightedIssues / max(1, $totalRows)) * 100;

            $score = (int) max(0, min(100, round(100 - $weightedPct)));
            $label = $score >= 90 ? 'OK' : ($score >= 70 ? 'Warning' : 'Critical');

            $analysis->update(['score' => $score]);

            $summary = [
                'overview' => [
                    'total_tables' => count($tableNames),
                    'total_rows' => $totalRows,
                    'total_checks' => $totalChecks,
                    'total_issues' => $totalIssues,
                    'error_percent' => $totalRows > 0 ? (int) round(($totalIssues / $totalRows) * 100) : 0,
                ],
                'tables' => [
                    'names' => $tableNames,
                    'row_counts' => $counts,
                ],
                'issues_by_type' => $checksSummary['issues_by_type'] ?? [],
                'severity' => [
                    'ok' => $okRows,
                    'warning' => $warn,
                    'critical' => $crit,
                ],

                'health' => [
                    'score' => $score,
                    'label' => $label,
                ],

                // корисно для фронта
                'profile' => $profile,
                'enabled_checks' => $keys,
            ];

            $analysis->update([
                'summary' => $summary,
                'progress' => 100,
                'status' => 'success',
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            $analysis->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'progress' => 100,
            ]);

            throw $e;
        } finally {
            // чистимо тільки тимчасові папки (zip)
            foreach (($prepared['cleanup_paths'] ?? []) as $p) {
                Storage::disk('local')->deleteDirectory($p);
            }
        }
    }
}
