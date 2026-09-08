<?php

namespace App\Http\Controllers\Api;

use App\Domain\DbAudit\Services\CheckFactory;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The catalogue of profiles and checks, translated server-side, so adding a
 * check does not require touching the frontend.
 */
class MetaController extends Controller
{
    public function __invoke(CheckFactory $factory): JsonResponse
    {
        $profiles = (array) config('db_audit.profiles', []);

        $checkKeys = array_keys((array) config('db_audit.checks', []));

        $checks = [];
        foreach ($checkKeys as $key) {
            $checks[$key] = [
                'key' => $key,
                'title' => __("checks.{$key}.title"),
                'description' => __("checks.{$key}.description"),
            ];
        }

        return response()->json([
            'data' => [
                'profiles' => collect($profiles)
                    ->map(fn ($profile, $key) => [
                        'key' => $key,
                        'checks' => $profile['checks'] ?? [],
                    ])
                    ->values(),
                'default_profile' => config('db_audit.default_profile'),
                'checks' => $checks,
                'max_upload_kb' => (int) config('db_audit.limits.max_upload_kb', 204800),
            ],
        ]);
    }
}
