<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\SettingController;
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

Route::middleware('api')->group(function () {
    Route::get('/health', [HealthController::class, 'checkHealth']);

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

            // ソーシャル認証（Twitter/X）- OAuth 1.0a はセッションが必要
            Route::middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
            ])->prefix('/social')->name('social.')->group(function () {
                Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('redirect');
                Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
            });

            Route::middleware('auth:api')->group(function () {
                Route::get('/me', [AuthController::class, 'me'])->name('me');
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
            });
        });

    Route::middleware('auth:api')->group(function () {
        // Events API
        Route::prefix('/events')->group(function () {
            Route::get('/', [EventController::class, 'index'])->name('events.index');
            Route::get('/{event}', [EventController::class, 'show'])->name('events.show');
            Route::post('/', [EventController::class, 'store'])->name('events.store');
            Route::put('/{event}', [EventController::class, 'update'])->name('events.update');
            Route::delete('/{event}', [EventController::class, 'destroy'])->name('events.destroy');

            // History API
            Route::prefix('/{event}/history')->group(function () {
                Route::get('/', [HistoryController::class, 'index'])->name('events.history.index');
                Route::post('/', [HistoryController::class, 'store'])->name('events.history.store');
                Route::put('/{history}', [HistoryController::class, 'update'])->name('events.history.update');
                Route::delete('/{history}', [HistoryController::class, 'destroy'])->name('events.history.destroy');
            });
        });

        // Settings API
        Route::get('/settings', [SettingController::class, 'show'])->name('settings.show');
        Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Export API
        Route::get('/export/csv', [ExportController::class, 'exportCsv'])->name('export.csv');
    });
});
