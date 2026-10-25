<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/verify', [AuthController::class, 'verify'])->name('api.v1.verify');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');
    });
});
