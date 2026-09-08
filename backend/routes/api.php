<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\MetaController;
use Illuminate\Support\Facades\Route;

Route::get('/meta', MetaController::class);

Route::post('/analyses', [AnalysisController::class, 'store'])
    ->middleware('throttle:uploads');

Route::prefix('/analyses/{analysis}')->group(function () {
    Route::get('/', [AnalysisController::class, 'show']);
    Route::get('/events', [AnalysisController::class, 'events']);
    Route::get('/findings', [AnalysisController::class, 'findings']);
    Route::get('/findings/summary', [AnalysisController::class, 'findingsSummary']);
    Route::get('/findings/export', [AnalysisController::class, 'exportFindings']);
});
