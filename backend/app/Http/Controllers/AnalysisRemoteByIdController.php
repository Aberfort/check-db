<?php

namespace App\Http\Controllers;

use App\Domain\DbAudit\Services\RemoteDbApiClient;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use Illuminate\Http\Request;

class AnalysisRemoteByIdController extends Controller
{
    public function store(Request $request, RemoteDbApiClient $client)
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'max:32'],
        ]);

        $profile = $data['profile'] ?? 'basic';
        $id = (string)$data['id'];

        $d = $client->buildDownloadUrl($id);

        // Створюємо analysis як remote
        $analysis = Analysis::create([
            'status' => 'queued',
            'progress' => 0,
            'profile' => $profile,
            'ingest' => 'remote',
            'remote_url' => $d['url'],
            'remote_headers' => null,
            'original_name' => null,
            'stored_path' => null,
        ]);

        ProcessAnalysisJob::dispatch($analysis->id);

        return response()->json(['ok' => true, 'data' => $analysis], 201);
    }
}
