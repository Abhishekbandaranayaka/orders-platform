<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
   // Read today’s KPIs from Redis
   Route::get('/kpi/today', [AnalyticsController::class, 'today']);
   
    // Read leaderboard
    Route::get('/leaderboard/top', [AnalyticsController::class, 'leaderboard']);


Route::middleware('api.token')->group(function () {
    // Queue a CSV import (either by file upload or server path)
    Route::post('/orders/import', [OrdersController::class, 'import']);

    // Queue a refund for an order
    Route::post('/orders/{id}/refund', [OrdersController::class, 'refund'])->whereNumber('id');


    Route::get('/dashboard', DashboardController::class);
});
