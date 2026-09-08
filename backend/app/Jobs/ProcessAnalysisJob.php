<?php

namespace App\Jobs;

use App\Domain\DbAudit\Adapters\SqliteAdapter;
use App\Domain\DbAudit\Contracts\DbInputPreparer;
use App\Domain\DbAudit\Services\AnalysisRunner;
use App\Domain\DbAudit\Services\CheckFactory;
use App\Models\Analysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;

class ProcessAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Progress reserved for opening and preparing the file, before checks start. */
    private const PREPARE_PROGRESS = 10;

    public function __construct(public int $analysisId) {}

    public function handle(
        DbInputPreparer $preparer,
        CheckFactory $checkFactory,
        AnalysisRunner $runner,
    ): void {
        $analysis = Analysis::query()->findOrFail($this->analysisId);

        $cleanupPaths = [];

        try {
            $analysis->update(['status' => 'processing', 'progress' => 1]);

            $disk = Storage::disk('local');

            if (! $analysis->stored_path || ! is_file($disk->path($analysis->stored_path))) {
                throw new RuntimeException('Uploaded database file is missing from storage.');
            }

            $prepared = $preparer->prepare($analysis, $analysis->stored_path, (string) $analysis->original_name);
            $cleanupPaths = $prepared['cleanup_paths'];

            $absolutePath = $disk->path($prepared['path']);
            if (! is_file($absolutePath)) {
                throw new RuntimeException('Prepared database file could not be opened.');
            }

            $analysis->update(['progress' => self::PREPARE_PROGRESS]);

            $db = new SqliteAdapter($this->connect($absolutePath));
            $checks = $checkFactory->forProfile($analysis->profile);

            $summary = $runner->run($analysis, $db, $checks, function (int $percent) use ($analysis): void {
                $analysis->update([
                    'progress' => self::PREPARE_PROGRESS
                        + (int) round($percent * (100 - self::PREPARE_PROGRESS) / 100),
                ]);
            });

            $analysis->update([
                'summary' => $summary,
                'score' => $summary['health']['score'],
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
            foreach ($cleanupPaths as $path) {
                Storage::disk('local')->deleteDirectory($path);
            }
        }
    }

    /**
     * The file is attacker-supplied, so the connection stays read-only and never
     * follows the database into other files.
     */
    private function connect(string $absolutePath): PDO
    {
        $pdo = new PDO('sqlite:'.$absolutePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('PRAGMA query_only = ON');
        $pdo->exec('PRAGMA trusted_schema = OFF');

        return $pdo;
    }
}
