<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuccessResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogoutController extends Controller
{
    /**
     * ユーザーログアウト処理
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Services\Auth\AuthService  $authService
     * @return \App\Http\Resources\SuccessResource
     */
    public function logout(Request $request, AuthService $authService): JsonResource
    {
        // ログアウト処理をサービスに委譲
        $authService->logoutUser($request->user());

        return new SuccessResource(null, 'ログアウトしました');
    }
} 