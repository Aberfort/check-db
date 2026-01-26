<?php

namespace App\Console\Commands;

use App\Models\Analysis;
use App\Models\Finding;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnalysesCleanupCommand extends Command
{
    protected $signature = 'analyses:cleanup {--dry-run : Тільки показати, що буде видалено}';
    protected $description = 'Cleanup old analyses: DB rows, findings, uploaded files';

    public function handle(): int
    {
        $ttlDays = (int) config('db_audit.cleanup.ttl_days', 14);
        $onlyFinished = (bool) config('db_audit.cleanup.only_finished', true);
        $limit = (int) config('db_audit.cleanup.limit', 200);

        $cutoff = Carbon::now()->subDays(max(1, $ttlDays));

        $q = Analysis::query()
                     ->where('created_at', '<', $cutoff)
                     ->orderBy('created_at')
                     ->limit(max(1, $limit));

        if ($onlyFinished) {
            $q->whereIn('status', ['success', 'error']);
        }

        $items = $q->get();

        if ($items->isEmpty()) {
            $this->info('Nothing to cleanup.');
            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');

        $this->line('Cutoff: ' . $cutoff->toDateTimeString());
        $this->line('Found: ' . $items->count());

        foreach ($items as $a) {
            $dir = 'uploads/analyses/' . $a->id;

            $this->line(sprintf(
                '- #%d status=%s created=%s dir=%s stored=%s',
                $a->id,
                $a->status,
                (string) $a->created_at,
                $dir,
                (string) ($a->stored_path ?? '')
            ));

            if ($dry) {
                continue;
            }

            DB::transaction(function () use ($a) {
                Finding::query()->where('analysis_id', $a->id)->delete();
                $a->delete();
            });

            Storage::disk('local')->deleteDirectory($dir);
        }

        $this->info($dry ? 'Dry-run done.' : 'Cleanup done.');

        return self::SUCCESS;
    }
}
