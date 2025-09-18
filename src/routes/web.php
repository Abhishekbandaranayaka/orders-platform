<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class);
Route::get('/kpi/today', [AnalyticsController::class, 'today']);