<?php

use App\Http\Controllers\AnalysisRemoteByIdController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\RemoteDbListController;
use App\Http\Controllers\SettingsRemoteDbController;
use Illuminate\Support\Facades\Route;

Route::post('/analyses', [AnalysisController::class, 'store']);
Route::get('/analyses/{analysis}', [AnalysisController::class, 'show']);
Route::get('/analyses/{analysis}/findings', [AnalysisController::class, 'findings']);
Route::get('/analyses/{analysis}/findings/export', [AnalysisController::class, 'exportFindings']);
Route::get('/analyses/{analysis}/events', [AnalysisController::class, 'events']);

Route::get('/settings/remote-db', [SettingsRemoteDbController::class, 'show']);
Route::put('/settings/remote-db', [SettingsRemoteDbController::class, 'update']);

Route::get('/remote-dbs', [RemoteDbListController::class, 'index']);
Route::post('/analyses/remote-by-id', [AnalysisRemoteByIdController::class, 'store']);
