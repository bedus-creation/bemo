<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

// SPA fallback: send all other routes to the SPA entry
Route::view('/{any}', 'app')->where('any', '.*');
