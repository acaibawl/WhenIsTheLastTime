<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HealthController;
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
            Route::post('/register/send-code', [AuthController::class, 'sendCode'])->name('register.send-code');
            Route::post('/register/verify', [AuthController::class, 'verifyCode'])->name('register.verify');
            Route::post('/register/resend-code', [AuthController::class, 'resendCode'])->name('register.resend-code');
            Route::post('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/login', [AuthController::class, 'login'])->name('login');
            Route::middleware('auth:api')->group(function () {
                Route::get('/me', [AuthController::class, 'me'])->name('me');
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
            });
        });
});
