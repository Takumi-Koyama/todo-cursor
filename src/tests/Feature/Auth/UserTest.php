<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーが自分のユーザー情報を取得できることをテスト
     */
    public function test_authenticated_user_can_get_user_info()
    {
        // テストユーザーを作成し認証状態にする
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        // レスポンスを検証
        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'created_at',
                'updated_at'
            ]);
    }

    /**
     * 未認証ユーザーがユーザー情報取得APIにアクセスした場合に認証エラー（401）が返されることをテスト
     */
    public function test_unauthenticated_user_cannot_get_user_info()
    {
        $response = $this->getJson('/api/v1/auth/user');

        // レスポンスを検証
        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * 無効なトークンで認証エラー（401）が返されることをテスト
     */
    public function test_user_with_invalid_token_cannot_get_user_info()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
        ])->getJson('/api/v1/auth/user');

        // レスポンスを検証
        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
} 