<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Auth\RegisterResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterController extends Controller
{
    /**
     * 新規ユーザー登録処理
     *
     * @param  \App\Http\Requests\Api\Auth\RegisterRequest  $request
     * @param  \App\Services\Auth\AuthService  $authService
     * @return \App\Http\Resources\Auth\RegisterResource
     */
    public function register(RegisterRequest $request, AuthService $authService): JsonResource
    {
        // バリデーション済みデータの取得
        $validated = $request->validated();
        
        // ユーザー作成（AuthServiceに委譲）
        $user = $authService->registerUser($validated);

        // リソースを使用してレスポンスを返却
        return new RegisterResource($user);
    }
} 