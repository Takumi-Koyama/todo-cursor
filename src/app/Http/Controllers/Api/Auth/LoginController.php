<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthTokenResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * ユーザーログイン処理
     *
     * @param  \App\Http\Requests\Api\Auth\LoginRequest  $request
     * @param  \App\Services\Auth\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\Auth\AuthTokenResource
     */
    public function login(LoginRequest $request, AuthService $authService)
    {
        // バリデーション済みデータの取得
        $validated = $request->validated();
        
        // 認証処理をサービスに委譲
        $result = $authService->authenticateUser($validated['email'], $validated['password']);
        
        // 認証失敗
        if (!$result) {
            return response()->json([
                'message' => '認証に失敗しました',
                'error' => 'メールアドレスまたはパスワードが正しくありません'
            ], 401);
        }
        
        // 認証成功：リソースを使用してレスポンスを返却
        return new AuthTokenResource(
            $result['user'], 
            $result['token'], 
            $result['expires_in']
        );
    }
}