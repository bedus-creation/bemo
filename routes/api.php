<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::prefix('tickets')->group(function (): void {
    Route::post('/', [TicketController::class, 'store']);
    Route::get('/', [TicketController::class, 'index']);
    Route::get('stats', [TicketController::class, 'stats']);
    Route::get('{ticket}', [TicketController::class, 'show']);
    Route::patch('{ticket}', [TicketController::class, 'update']);
    Route::post('{ticket}/classify', [TicketController::class, 'classify']);
});
