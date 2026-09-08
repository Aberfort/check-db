<?php

namespace App\Providers;

use App\Domain\DbAudit\Contracts\DbInputPreparer;
use App\Domain\DbAudit\Services\DbInputPreparerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DbInputPreparer::class, DbInputPreparerService::class);
    }

    public function boot(): void
    {
        // Analysing an upload is expensive, so the public demo caps how often
        // one client can start a run.
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
