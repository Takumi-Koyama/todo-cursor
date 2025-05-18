# サービスレイヤーパターン

## 概要

サービスレイヤーパターンは、ビジネスロジックをコントローラーから分離し、専用のサービスクラスに移動させるアーキテクチャパターンです。この分離により、コードの責務が明確になり、再利用性、テスト容易性、保守性が向上します。

## 目的

1. **関心の分離**: コントローラーはHTTPリクエスト/レスポンスの処理に専念し、ビジネスロジックはサービスクラスに委譲します
2. **再利用性の向上**: 複数のコントローラーやジョブから同じビジネスロジックを利用できます
3. **テスト容易性**: ビジネスロジックが分離されているため、単体テストが容易になります
4. **コードの肥大化防止**: コントローラーがシンプルになり、単一責務の原則に従います

## ディレクトリ構造

サービスクラスは以下のディレクトリ構造に配置します：

```
app/
  Services/
    Auth/
      AuthService.php
    Todo/
      TodoService.php
    User/
      UserService.php
    ...
```

機能ごとにディレクトリを分け、関連するサービスクラスをまとめることで、コードの整理が容易になります。

## サービスクラスの実装例

```php
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

        // パスワード検証
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

## コントローラーでの利用例

### メソッドインジェクション

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * 新規ユーザー登録処理
     *
     * @param  \App\Http\Requests\Api\Auth\RegisterRequest  $request
     * @param  \App\Services\Auth\AuthService  $authService
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request, AuthService $authService): JsonResponse
    {
        // バリデーション済みデータの取得
        $validated = $request->validated();
        
        // ユーザー作成（AuthServiceに委譲）
        $user = $authService->registerUser($validated);

        return response()->json([
            'message' => 'ユーザー登録が完了しました',
            'user' => new UserResource($user)
        ], 201);
    }
}
```

### コンストラクタインジェクション

複数のメソッドで同じサービスを使用する場合は、コンストラクタインジェクションが適しています：

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Todo\TodoService;

class TodoController extends Controller
{
    /**
     * TodoServiceのインスタンス
     * 
     * @var \App\Services\Todo\TodoService
     */
    protected $todoService;

    /**
     * コンストラクタ
     * 
     * @param \App\Services\Todo\TodoService $todoService
     * @return void
     */
    public function __construct(TodoService $todoService)
    {
        $this->todoService = $todoService;
    }

    // 複数のメソッドで$this->todoServiceを使用...
}
```

## ガイドライン

1. **サービスの責務**:
   - サービスクラスは特定のドメインに関するビジネスロジックのみを扱います
   - モデルに関する操作（作成、更新、削除など）はサービスに委譲します
   - 複雑なクエリやトランザクションもサービスで実装します

2. **コントローラーの責務**:
   - HTTPリクエストの受け取りとバリデーション
   - 適切なサービスメソッドの呼び出し
   - レスポンスの整形と返却

3. **リソースクラスの活用**:
   - サービスからのデータをそのままレスポンスに使用せず、Resourceクラスを通して整形します

4. **例外処理**:
   - ビジネスロジックに関連する例外はサービス内で発生させ、コントローラーでキャッチして適切なレスポンスを返します

## ベストプラクティス

1. サービスのメソッドは単一の責務を持ち、明確な名前を付けます
2. 戻り値の型を明示し、PHPDocを適切に記述します
3. 複雑なロジックには単体テストを作成します
4. トランザクションを使用する場合は、サービス内で完結させます
5. モデルへの直接アクセスは極力サービスに委譲し、コントローラーでは行わないようにします 