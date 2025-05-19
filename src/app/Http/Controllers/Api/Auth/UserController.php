<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    /**
     * 現在認証されているユーザーの情報を取得
     *
     * このエンドポイントは認証（auth:sanctum）ミドルウェアで保護されているため、
     * 未認証ユーザーのリクエストは401エラーで自動的に拒否されます。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\UserResource
     */
    public function getCurrentUser(Request $request): JsonResource
    {
        // 認証ユーザーを取得
        $user = $request->user();

        // ユーザー情報をリソースとして返却
        return new UserResource($user);
    }
}