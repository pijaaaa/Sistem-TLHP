<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, '__invoke']);

Route::prefix('v1')->group(function () {
    // Routes v1 akan ditambahkan di milestone berikutnya
});
