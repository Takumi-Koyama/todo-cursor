<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthTokenResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * ユーザーログイン処理
     *
     * @param  \App\Http\Requests\Api\Auth\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        // バリデーション済みデータの取得
        $validated = $request->validated();
        
        // ユーザー検索
        $user = User::where('email', $validated['email'])->first();

        // 認証チェック
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => '認証に失敗しました',
                'error' => 'メールアドレスまたはパスワードが正しくありません'
            ], 401);
        }

        // トークン作成
        $token = $user->createToken('auth_token')->plainTextToken;
        $expiresIn = config('sanctum.expiration', 60 * 24) * 60;

        // リソースを使用してレスポンスを返却
        return new AuthTokenResource($user, $token, $expiresIn);
    }
}