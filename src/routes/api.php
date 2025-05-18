<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\UserController;

// APIバージョン1のルートグループ
Route::prefix('v1')->group(function () {
    // 認証関連
    Route::prefix('auth')->group(function () {
        Route::post('/register', [RegisterController::class, 'register']);
        Route::post('/login', [LoginController::class, 'login']);
        Route::post('/logout', [LogoutController::class, 'logout']);
        
        // 認証が必要なエンドポイント
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', [UserController::class, 'getCurrentUser']);
        });
    });
});
