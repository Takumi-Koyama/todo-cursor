<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * ユーザーログアウト処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // 認証済みユーザーの場合は、現在のトークンを削除
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return new SuccessResource(null, 'ログアウトしました');
    }
} 