<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン中のユーザーがログアウトできることをテスト
     */
    public function test_authenticated_user_can_logout()
    {
        // テストユーザーを作成し認証状態にする
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        // レスポンスを検証
        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'ログアウトしました'
            ]);

        // トークンが削除されたことを確認
        $this->assertCount(0, $user->tokens);
    }

    /**
     * 未認証ユーザーでもログアウトエンドポイントに対して200が返却されることをテスト
     */
    public function test_unauthenticated_user_can_also_use_logout_endpoint()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        // レスポンスを検証（未認証でも成功レスポンスを返す）
        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'ログアウトしました'
            ]);
    }
} 