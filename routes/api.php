<?php

declare(strict_types=1);

use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\TicketClassifyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketExportController;
use App\Http\Controllers\TicketStatsController;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'api.'], function (): void {
    Route::get('categories', TicketCategoryController::class)->name('categories.index');
    Route::get('tickets/stats', TicketStatsController::class)->name('tickets.stats.index');
    Route::get('tickets/export', TicketExportController::class);

    Route::apiResource('tickets', TicketController::class);
    Route::apiResource('tickets.classify', TicketClassifyController::class)->only('store');
});
