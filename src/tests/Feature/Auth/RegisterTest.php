<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * 新規ユーザー登録が成功することをテスト
     */
    public function test_user_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // ※明示的に201を返してはいないが、Laravelのデフォルトで変換されるので注意
        // https://github.com/mathieutu/framework/blob/f8af1166169d98a12af54aae8ceff87cea55bbbd/src/Illuminate/Routing/Router.php#L704-L705
        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * 必須項目が欠けている場合、バリデーションエラーになることをテスト
     */
    public function test_user_cannot_register_with_missing_required_fields()
    {
        // 名前がない
        $response1 = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response1
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // メールがない
        $response2 = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response2
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // パスワードがない
        $response3 = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $response3
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 不正なメールアドレス形式の場合、バリデーションエラーになることをテスト
     */
    public function test_user_cannot_register_with_invalid_email()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * パスワードが短すぎる場合、バリデーションエラーになることをテスト
     */
    public function test_user_cannot_register_with_short_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * パスワード確認が一致しない場合、バリデーションエラーになることをテスト
     */
    public function test_user_cannot_register_with_password_mismatch()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 既存ユーザーと同じメールアドレスで登録できないことをテスト
     */
    public function test_user_cannot_register_with_existing_email()
    {
        // 最初のユーザーを登録
        $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー1',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 同じメールアドレスで2人目のユーザーを登録しようとする
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'テストユーザー2',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
} 