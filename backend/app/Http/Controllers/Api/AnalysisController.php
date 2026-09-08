<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDbRequest;
use App\Http\Resources\FindingResource;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use App\Models\Finding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalysisController extends Controller
{
    public function store(UploadDbRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $analysis = Analysis::create([
            'status' => 'queued',
            'progress' => 0,
            'original_name' => $file->getClientOriginalName(),
            'db_type' => 'sqlite',
            'profile' => $request->profile(),
        ]);

        $base = Str::slug(pathinfo($analysis->original_name, PATHINFO_FILENAME)) ?: 'database';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'db');

        $analysis->update([
            'stored_path' => $file->storeAs("uploads/analyses/{$analysis->id}", "{$base}.{$extension}", 'local'),
        ]);

        ProcessAnalysisJob::dispatch($analysis->id);

        return response()->json(['data' => $this->present($analysis)], 201);
    }

    /** Runs the audit against the database bundled with the app. */
    public function storeSample(Request $request): JsonResponse
    {
        $source = database_path('samples/shop-demo.sqlite');

        abort_unless(is_file($source), 404, 'The sample database is not available.');

        $profile = $request->input('profile');
        $profiles = array_keys((array) config('db_audit.profiles', []));

        $analysis = Analysis::create([
            'status' => 'queued',
            'progress' => 0,
            'original_name' => 'shop-demo.sqlite',
            'db_type' => 'sqlite',
            'profile' => in_array($profile, $profiles, true)
                ? $profile
                : (string) config('db_audit.default_profile'),
        ]);

        $path = "uploads/analyses/{$analysis->id}/shop-demo.sqlite";
        Storage::disk('local')->put($path, file_get_contents($source));

        $analysis->update(['stored_path' => $path]);

        ProcessAnalysisJob::dispatch($analysis->id);

        return response()->json(['data' => $this->present($analysis)], 201);
    }

    public function show(Analysis $analysis): JsonResponse
    {
        return response()->json(['data' => $this->present($analysis)]);
    }

    public function findings(Request $request, Analysis $analysis): JsonResponse
    {
        $perPage = min(100, max(10, (int) $request->query('per_page', 25)));

        $items = $this->filtered($request, $analysis)->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => FindingResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Counts computed by the database instead of by paging every finding to the
     * client, which is what the histogram used to require.
     */
    public function findingsSummary(Request $request, Analysis $analysis): JsonResponse
    {
        $bySeverity = $analysis->findings()
            ->selectRaw('severity, COUNT(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $byCheck = $analysis->findings()
            ->selectRaw('check_key, severity, COUNT(*) as total')
            ->groupBy('check_key', 'severity')
            ->get()
            ->groupBy('check_key')
            ->map(fn ($rows) => $rows->pluck('total', 'severity'));

        $buckets = collect();
        if ($checkKey = $request->query('check_key')) {
            $buckets = $analysis->findings()
                ->where('check_key', $checkKey)
                ->whereNotNull('bucket')
                ->selectRaw('bucket, severity, COUNT(*) as total')
                ->groupBy('bucket', 'severity')
                ->orderByDesc('total')
                ->limit(50)
                ->get()
                ->map(fn ($row) => [
                    'bucket' => $row->bucket,
                    'severity' => $row->severity,
                    'total' => (int) $row->total,
                ]);
        }

        return response()->json([
            'data' => [
                'by_severity' => $bySeverity,
                'by_check' => $byCheck,
                'buckets' => $buckets->values(),
            ],
        ]);
    }

    public function exportFindings(Request $request, Analysis $analysis): StreamedResponse
    {
        $query = $this->filtered($request, $analysis);
        $filename = "findings-analysis-{$analysis->id}.csv";

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');

            // BOM so Excel reads UTF-8 correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['id', 'check', 'severity', 'table', 'column', 'message', 'row_ref', 'meta', 'created_at']);

            $query->chunkById(1000, function ($rows) use ($out) {
                foreach ($rows as $finding) {
                    fputcsv($out, [
                        $finding->id,
                        $finding->check_key,
                        $finding->severity,
                        $finding->table_name,
                        $finding->column_name,
                        $finding->message(),
                        $finding->row_ref ? json_encode($finding->row_ref, JSON_UNESCAPED_UNICODE) : '',
                        $finding->meta ? json_encode($finding->meta, JSON_UNESCAPED_UNICODE) : '',
                        $finding->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /** Server-sent events, so the client does not have to poll while a run is in flight. */
    public function events(Analysis $analysis): StreamedResponse
    {
        return response()->stream(function () use ($analysis) {
            $lastPayload = null;

            while (! connection_aborted()) {
                $fresh = $analysis->fresh();

                if (! $fresh) {
                    break;
                }

                $payload = json_encode($this->present($fresh), JSON_UNESCAPED_UNICODE);

                if ($payload !== $lastPayload) {
                    echo "event: analysis\n";
                    echo "data: {$payload}\n\n";

                    @ob_flush();
                    @flush();

                    $lastPayload = $payload;
                }

                if ($fresh->isFinished()) {
                    break;
                }

                usleep(700_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function filtered(Request $request, Analysis $analysis)
    {
        $query = Finding::query()->where('analysis_id', $analysis->id);

        $query->when($request->query('check_key'), fn ($q, $v) => $q->where('check_key', $v));
        $query->when($request->query('bucket'), fn ($q, $v) => $q->where('bucket', $v));

        $severity = $request->query('severity');
        if ($severity && $severity !== 'all') {
            $query->where('severity', $severity);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($search) {
                $w->where('table_name', 'like', "%{$search}%")
                    ->orWhere('column_name', 'like', "%{$search}%")
                    ->orWhere('bucket', 'like', "%{$search}%");
            });
        }

        return match ($request->query('sort', 'severity')) {
            'old' => $query->orderBy('id'),
            'new' => $query->orderByDesc('id'),
            default => $query
                ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
                ->orderBy('id'),
        };
    }

    private function present(Analysis $analysis): array
    {
        return [
            'id' => $analysis->id,
            'status' => $analysis->status,
            'progress' => $analysis->progress,
            'profile' => $analysis->profile,
            'original_name' => $analysis->original_name,
            'score' => $analysis->score,
            'summary' => $analysis->summary,
            'error_message' => $analysis->error_message,
            'created_at' => $analysis->created_at?->toIso8601String(),
        ];
    }
}
