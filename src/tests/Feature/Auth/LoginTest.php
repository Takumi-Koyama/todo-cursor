<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * 有効な認証情報でログインできることをテスト
     */
    public function test_user_can_login_with_valid_credentials()
    {
        // テストユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * 存在しないメールアドレスで認証エラー（401）になることをテスト
     */
    public function test_user_cannot_login_with_nonexistent_email()
    {
        // テストユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => '認証に失敗しました',
                'error' => 'メールアドレスまたはパスワードが正しくありません'
            ]);
    }

    /**
     * 無効なパスワードで認証エラー（401）になることをテスト
     */
    public function test_user_cannot_login_with_invalid_password()
    {
        // テストユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => '認証に失敗しました',
                'error' => 'メールアドレスまたはパスワードが正しくありません'
            ]);
    }

    /**
     * 必須フィールドが欠けている場合、バリデーションエラーになることをテスト
     */
    public function test_user_cannot_login_with_missing_fields()
    {
        // メールアドレスが欠けている
        $response1 = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response1
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // パスワードが欠けている
        $response2 = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response2
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 不正なメールアドレス形式の場合、バリデーションエラーになることをテスト
     */
    public function test_user_cannot_login_with_invalid_email_format()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
} 