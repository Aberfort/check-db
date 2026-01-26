<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDbRequest;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use App\Models\Finding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalysisController extends Controller
{
    public function store(UploadDbRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $profile = $request->input('profile', 'basic');

        $analysis = Analysis::create([
            'status' => 'queued',
            'progress' => 0,
            'original_name' => $file->getClientOriginalName(),
            'db_type' => 'sqlite',
            'profile' => $profile,
        ]);

        $base = Str::slug(pathinfo($analysis->original_name, PATHINFO_FILENAME)) ?: 'database';
        $ext = $file->getClientOriginalExtension() ?: 'db';

        $storedPath = $file->storeAs("uploads/analyses/{$analysis->id}", "{$base}.{$ext}", 'local');

        $analysis->update(['stored_path' => $storedPath]);

        ProcessAnalysisJob::dispatch($analysis->id);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $analysis->id,
                'status' => $analysis->status,
                'progress' => $analysis->progress,
            ],
        ], 201);
    }

    public function show(Analysis $analysis): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $analysis->id,
                'status' => $analysis->status,
                'progress' => $analysis->progress,
                'db_type' => $analysis->db_type,
                'original_name' => $analysis->original_name,
                'summary' => $analysis->summary,
                'error_message' => $analysis->error_message,
                'created_at' => $analysis->created_at,
            ],
            'score' => $analysis->score,
        ]);
    }

    public function findings(Analysis $analysis): JsonResponse
    {
        $q = Finding::query()->where('analysis_id', $analysis->id);

        if ($check = request()->query('check_key')) {
            $q->where('check_key', $check);
        }

        if ($sev = request()->query('severity')) {
            $q->where('severity', $sev);
        }

        if ($qText = trim((string) request()->query('q', ''))) {
            $q->where(function ($w) use ($qText) {
                $w->where('message', 'like', "%{$qText}%")
                  ->orWhere('table_name', 'like', "%{$qText}%")
                  ->orWhere('column_name', 'like', "%{$qText}%");
            });
        }

        $sort = request()->query('sort', 'new');
        if ($sort === 'old') {
            $q->orderBy('id', 'asc');
        } elseif ($sort === 'severity') {
            $q->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
              ->orderByDesc('id');
        } else {
            $q->orderByDesc('id');
        }

        $perPage = min(100, max(10, (int) request()->query('per_page', 25)));

        $items = $q->paginate($perPage);

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function exportFindings(Analysis $analysis)
    {
        $q = Finding::query()->where('analysis_id', $analysis->id);

        if ($check = request()->query('check_key')) {
            $q->where('check_key', $check);
        }
        if ($sev = request()->query('severity')) {
            $q->where('severity', $sev);
        }
        if ($qText = trim((string) request()->query('q', ''))) {
            $q->where(function ($w) use ($qText) {
                $w->where('message', 'like', "%{$qText}%")
                  ->orWhere('table_name', 'like', "%{$qText}%")
                  ->orWhere('column_name', 'like', "%{$qText}%");
            });
        }

        $sort = request()->query('sort', 'new');
        if ($sort === 'old') {
            $q->orderBy('id', 'asc');
        } elseif ($sort === 'severity') {
            $q->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
              ->orderByDesc('id');
        } else {
            $q->orderByDesc('id');
        }

        $filename = 'findings-analysis-' . $analysis->id . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($q) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM щоб Excel не ламав кирилицю
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['id','check_key','severity','table','column','message','row_ref','meta','created_at']);

            $q->chunkById(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        $r->check_key,
                        $r->severity,
                        $r->table_name,
                        $r->column_name,
                        $r->message,
                        $r->row_ref ? json_encode($r->row_ref, JSON_UNESCAPED_UNICODE) : '',
                        $r->meta ? json_encode($r->meta, JSON_UNESCAPED_UNICODE) : '',
                        $r->created_at?->toISOString(),
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }

    public function events(Request $request, Analysis $analysis): StreamedResponse
    {
        return response()->stream(function () use ($analysis) {
            // SSE: одне з’єднання, шлемо події поки не success/error
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $a = $analysis->fresh();

                $payload = [
                    'id' => $a->id,
                    'status' => $a->status,
                    'progress' => $a->progress,
                    'score' => $a->score,
                    'error_message' => $a->error_message,
                    'summary' => $a->summary,
                    'updated_at' => optional($a->updated_at)?->toISOString(),
                ];

                echo "event: analysis\n";
                echo "data: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";

                @ob_flush();
                @flush();

                if (in_array($a->status, ['success', 'error'], true)) {
                    break;
                }

                // keepalive/poll interval
                usleep(700000); // 0.7s
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // важливо для nginx
        ]);
    }
}
