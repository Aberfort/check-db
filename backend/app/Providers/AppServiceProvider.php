<?php

namespace App\Providers;

use App\Domain\DbAudit\Contracts\DbInputPreparer;
use App\Domain\DbAudit\Services\DbInputPreparerService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DbInputPreparer::class,
            DbInputPreparerService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
