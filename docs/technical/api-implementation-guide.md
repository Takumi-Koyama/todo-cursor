# API実装ガイド

## 概要

このドキュメントでは、開発フローに沿ったAPI実装の具体的な手順とサンプルコードを提供します。各ステップでのベストプラクティスとコーディング例を参照して、一貫性のある高品質なAPIを実装してください。

## 実装例: ユーザー認証API

以下に、ユーザー認証APIの実装例を開発フロー順に紹介します。

### 1. OpenAPI仕様の確認

まず、`docs/openapi.yaml`でエンドポイントの定義を確認します：

```yaml
# 例：ログインエンドポイントの定義
/auth/login:
  post:
    summary: ログイン
    description: 登録済みユーザーがログインします
    operationId: loginUser
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/AuthLoginRequest'
    responses:
      '200':
        description: ログイン成功
        content:
          application/json:
            schema:
              allOf:
                - $ref: '#/components/schemas/TokenResponse'
                - type: object
                  properties:
                    user:
                      $ref: '#/components/schemas/UserResponse'
      '401':
        description: 認証エラー
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/AuthErrorResponse'
```

### 3. テストコードの実装

APIの動作を検証するテストコードを作成します：

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_user_cannot_login_with_nonexistent_email()
    {
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

    // 他のテストケース...
}
```

### 5. シンプルな実装

テストにパスするための最小限の実装を行います：

#### ルート定義

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [LoginController::class, 'login']);
    });
});
```

#### コントローラー実装

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => '認証に失敗しました',
                'error' => 'メールアドレスまたはパスワードが正しくありません'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 60 * 24) * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }
}
```

### 7. リファクタリング

#### FormRequestの作成

```php
<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }
}
```

#### Resourceクラスの作成

```php
<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    protected $token;
    protected $expiresIn;

    public function __construct($user, $token, $expiresIn)
    {
        parent::__construct($user);
        $this->token = $token;
        $this->expiresIn = $expiresIn;
    }

    public function toArray($request)
    {
        return [
            'access_token' => $this->token,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
            'user' => new UserResource($this->resource),
        ];
    }
}
```

#### リファクタリング後のコントローラー

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthTokenResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * ユーザーログイン処理
     *
     * @param  \App\Http\Requests\Api\Auth\LoginRequest  $request
     * @param  \App\Services\Auth\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\Auth\AuthTokenResource
     */
    public function login(LoginRequest $request, AuthService $authService)
    {
        // バリデーション済みデータの取得
        $validated = $request->validated();
        
        // 認証処理をサービスに委譲
        $result = $authService->authenticateUser($validated['email'], $validated['password']);
        
        // 認証失敗
        if (!$result) {
            return response()->json([
                'message' => '認証に失敗しました',
                'error' => 'メールアドレスまたはパスワードが正しくありません'
            ], 401);
        }
        
        // 認証成功：リソースを使用してレスポンスを返却
        return new AuthTokenResource(
            $result['user'], 
            $result['token'], 
            $result['expires_in']
        );
    }
}
```

#### サービスクラスの実装

```php
<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
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
```

## サービスレイヤーパターンの活用

上記のリファクタリング例では、サービスレイヤーパターンを活用してビジネスロジックをコントローラーから分離しています。このパターンの利点は以下の通りです：

1. **コントローラーがスリムに**: コントローラーはHTTPリクエスト/レスポンスの処理に集中し、ビジネスロジックは別のクラスに委譲されます
2. **再利用性の向上**: 認証ロジックは複数の場所から利用できるようになります
3. **テスト容易性**: サービスクラスは単体でテスト可能です
4. **拡張性**: 新しい認証方法を追加する場合も、サービスクラスの拡張だけで対応できます

詳細については、[サービスレイヤーパターン](./service-layer.md)のドキュメントを参照してください。

## ベストプラクティス

1. **責務の分離**:
   - コントローラー: HTTPリクエスト/レスポンスの処理
   - FormRequest: バリデーション
   - Service: ビジネスロジック
   - Resource: レスポンスの整形

2. **PHPドキュメント**:
   - すべてのクラスとメソッドには適切なPHPDocを記述する
   - パラメータと戻り値の型を明示する

3. **型の活用**:
   - メソッドのパラメータと戻り値に型宣言を使用する
   - nullable型（例: ?array）を適切に活用する

4. **例外処理**:
   - 予期せぬエラーには例外を投げる
   - グローバルな例外ハンドラーでキャッチして適切なレスポンスに変換する

## 一般的なベストプラクティス

### JSONレスポンスの構造

すべてのAPIレスポンスは一貫した構造を持つべきです：

1. **成功レスポンス**:
   - ステータスコード: 200、201など
   - データ構造: Resourceクラスで定義された形式

2. **エラーレスポンス**:
   - ステータスコード: 400、401、403、404、422など
   - データ構造:
     ```json
     {
       "message": "エラーの簡単な説明",
       "errors": {
         "field1": ["エラーメッセージ1", "エラーメッセージ2"],
         "field2": ["エラーメッセージ"]
       }
     }
     ```

### コントローラの責務

コントローラーは以下の責務を担います：

1. リクエストの受け取りと検証（FormRequestを使用）
2. 認可チェック（Policyを使用）
3. サービスやモデルを通じたビジネスロジックの実行
4. レスポンスの返却（Resourceを使用）

複雑なロジックはサービスクラスに移動し、コントローラーはシンプルに保ちましょう。

### バリデーションのベストプラクティス

1. 常にFormRequestクラスを使用してバリデーションを行う
2. バリデーションルールは明示的に配列形式で記述する
3. 複雑なバリデーションロジックは`withValidator`メソッドに実装する
4. データの前処理は`prepareForValidation`メソッドに実装する

### 認証・認可

1. 認証にはLaravel Sanctumを使用
2. 認可にはPolicyを使用
3. 適切なスコープを設定してトークンの権限を制限

### エラーハンドリング

1. 例外はtry-catchでキャッチし、適切なJSONレスポンスに変換
2. ValidationExceptionは自動的に422レスポンスに変換される
3. ModelNotFoundExceptionは404レスポンスに変換する
4. 認証・認可の例外は401または403レスポンスに変換する

## まとめ

この開発フローとガイドラインに従うことで、一貫性があり保守しやすいAPIを実装できます。各ステップでの判断に迷った際は、このドキュメントを参照してください。 