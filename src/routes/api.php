<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;

// APIバージョン1のルートグループ
Route::prefix('v1')->group(function () {
    // 認証関連
    Route::prefix('auth')->group(function () {
        Route::post('/register', [RegisterController::class, 'register']);
    });
    
    // 認証が必要なエンドポイント
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});
