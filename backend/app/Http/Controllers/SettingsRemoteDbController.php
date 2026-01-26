<?php

namespace App\Http\Controllers;

use App\Domain\DbAudit\Services\RemoteSettingsStore;
use Illuminate\Http\Request;

class SettingsRemoteDbController extends Controller
{
    public function show(RemoteSettingsStore $store)
    {
        return response()->json(['ok' => true, 'data' => $store->get()]);
    }

    public function update(Request $request, RemoteSettingsStore $store)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],

            'base_url' => ['required', 'string', 'max:2048'],
            'list_path' => ['nullable', 'string', 'max:2048'],
            'download_path' => ['nullable', 'string', 'max:2048'],

            'bearer' => ['nullable', 'string', 'max:4096'],

            'basic_user' => ['nullable', 'string', 'max:255'],
            'basic_pass' => ['nullable', 'string', 'max:255'],
        ]);

        $store->put($data);

        return response()->json(['ok' => true, 'data' => $store->get()]);
    }
}
