<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * 新規ユーザー登録処理
     *
     * @param  \App\Http\Requests\Api\Auth\RegisterRequest  $request
     * @param  \App\Services\Auth\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request, AuthService $authService): JsonResponse
    {
        // バリデーション済みデータの取得
        $validated = $request->validated();
        
        // ユーザー作成（AuthServiceに委譲）
        $user = $authService->registerUser($validated);

        return response()->json([
            'message' => 'ユーザー登録が完了しました',
            'user' => new UserResource($user)
        ], 201);
    }
} 