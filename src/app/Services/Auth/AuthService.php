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
} 