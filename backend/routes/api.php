<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::get('/health', [HealthController::class, 'checkHealth']);

Route::middleware('api')->group(function () {
    Route::prefix('auth')
        ->name('auth.')
        ->group(function () {
            Route::prefix('/register')->group(function () {
                // 1分間に5回までのリクエストを許可
                Route::post('/send-code', [AuthController::class, 'sendVerificationCode'])
                    ->middleware('throttle:5,1')
                    ->name('register.send-code');
                Route::post('/verify', [AuthController::class, 'verifyRegistrationCode'])->name('register.verify');
                Route::post('/resend-code', [AuthController::class, 'resendVerificationCode'])->name('register.resend-code');
            });
            Route::post('/login', [AuthController::class, 'login'])->name('login');
            Route::middleware('auth:api')->group(function () {
                Route::get('/me', [AuthController::class, 'me'])->name('me');
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
            });
        });

    Route::middleware('auth:api')->group(function () {
        // Events API
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

        // History API
        Route::get('/events/{event}/history', [HistoryController::class, 'index'])->name('events.history.index');
        Route::post('/events/{event}/history', [HistoryController::class, 'store'])->name('events.history.store');
    });
});
