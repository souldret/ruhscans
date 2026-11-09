<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HandshakeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:30,1')
        ->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/verify', [AuthController::class, 'verify'])->name('api.v1.verify');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');
        Route::post('/launcher/handshake', HandshakeController::class)
            ->middleware('throttle:30,1')
            ->name('api.v1.launcher.handshake');
    });
});
