<?php

namespace App\Http\Controllers;

use App\Domain\DbAudit\Services\RemoteDbApiClient;
use RuntimeException;
use Throwable;

class RemoteDbListController extends Controller
{
    public function index(RemoteDbApiClient $client)
    {
        try {
            $items = $client->list();

            return response()->json([
                'ok' => true,
                'data' => $items,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'code' => 'REMOTE_API_DISABLED',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'code' => 'REMOTE_API_FAILED',
                'message' => 'Remote API недоступний або повернув помилку.',
            ], 502);
        }

        //return response()->json([
        //    'ok' => true,
        //    'data' => $items,
        //]);
    }
}
