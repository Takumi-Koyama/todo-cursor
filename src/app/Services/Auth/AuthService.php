<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * 新規ユーザーを登録する
     *
     * @param array $userData ユーザーデータ (name, email, password)
     * @return User 作成されたユーザーモデル
     */
    public function registerUser(array $userData): User
    {
        // ユーザー作成
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        return $user;
    }

    /**
     * ユーザー認証を行う
     *
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return array|null 認証成功時はユーザーとトークン情報、失敗時はnull
     */
    public function authenticateUser(string $email, string $password): ?array
    {
        // ユーザー検索
        $user = User::where('email', $email)->first();

        // 認証チェック
        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        // トークン作成
        $token = $user->createToken('auth_token')->plainTextToken;
        $expiresIn = config('sanctum.expiration', 60 * 24) * 60;

        return [
            'user' => $user,
            'token' => $token,
            'expires_in' => $expiresIn
        ];
    }
} 