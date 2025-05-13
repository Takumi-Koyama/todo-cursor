# Laravelの認可設計 - ポリシー中心アプローチ

## 設計原則

TODOアプリの認可システムは、Laravelのポリシー機能を中心に構築します：

### 基本方針

- **ポリシー中心のアプローチ**: 認可ロジックはポリシークラスに集約
- **モデル単位の権限管理**: 各モデル（Todo, Category等）ごとにポリシークラスを作成
- **明確な責務分離**: 認証（Authentication）と認可（Authorization）の明確な分離
- **一貫したアクセス制御**: アプリケーション全体で統一された権限チェックパターン

### 具体的な実装方針

#### ポリシーの構成
- 各モデルに対応するポリシークラスを作成
- 標準CRUDアクション（viewAny, view, create, update, delete）をベースに実装
- 必要に応じてカスタムアクション（complete, reorder等）を追加

#### コントローラーとの連携
- 各アクションの先頭で`authorize()`メソッドによる権限チェック
- ルートモデルバインディングを活用した効率的な実装

#### ビューとの連携
- `@can`/`@cannot`ディレクティブを使用したUI要素の表示制御
- 権限に基づいた操作ボタンの表示/非表示の一貫した管理

## ポリシークラスの実装例

### Todoポリシーの基本実装

```php
// app/Policies/TodoPolicy.php
namespace App\Policies;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TodoPolicy
{
    use HandlesAuthorization;

    // 一覧表示権限
    public function viewAny(User $user)
    {
        return true; // 認証済みユーザーは自分のTODO一覧を表示可能
    }

    // 詳細表示権限
    public function view(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id;
    }

    // 作成権限
    public function create(User $user)
    {
        return true; // 認証済みユーザーはTODO作成可能
    }

    // 更新権限
    public function update(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id;
    }

    // 削除権限
    public function delete(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id;
    }
    
    // 完了マーク権限（カスタムアクション）
    public function complete(User $user, Todo $todo)
    {
        return $user->id === $todo->user_id;
    }
}
```

### 管理者権限の実装例

```php
// app/Policies/TodoPolicy.php の先頭に追加
// 管理者はすべての操作を許可
public function before(User $user, $ability)
{
    if ($user->isAdmin()) {
        return true;
    }
    
    return null; // null を返すと通常のポリシーチェックに進む
}
```

## ポリシーの使用方法

### コントローラーでの使用例

```php
// app/Http/Controllers/TodoController.php
namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // 認証が必要
    }
    
    // 一覧表示
    public function index()
    {
        $this->authorize('viewAny', Todo::class);
        
        // 自分のTodoのみ取得
        $todos = Todo::where('user_id', auth()->id())->get();
        
        return view('todos.index', compact('todos'));
    }
    
    // 詳細表示
    public function show(Todo $todo)
    {
        $this->authorize('view', $todo);
        
        return view('todos.show', compact('todo'));
    }
    
    // 編集フォーム
    public function edit(Todo $todo)
    {
        $this->authorize('update', $todo);
        
        return view('todos.edit', compact('todo'));
    }
    
    // 更新処理
    public function update(Request $request, Todo $todo)
    {
        $this->authorize('update', $todo);
        
        $todo->update($request->validated());
        
        return redirect()->route('todos.show', $todo);
    }
    
    // 削除処理
    public function destroy(Todo $todo)
    {
        $this->authorize('delete', $todo);
        
        $todo->delete();
        
        return redirect()->route('todos.index');
    }
    
    // 完了マーク処理（カスタムアクション）
    public function complete(Todo $todo)
    {
        $this->authorize('complete', $todo);
        
        $todo->markAsCompleted();
        
        return redirect()->back();
    }
}
```

### ビューでの使用例

```blade
{{-- resources/views/todos/show.blade.php --}}

<h1>{{ $todo->title }}</h1>
<p>{{ $todo->description }}</p>

{{-- 権限に基づいたアクション表示 --}}
@can('update', $todo)
    <a href="{{ route('todos.edit', $todo) }}" class="btn btn-primary">編集</a>
@endcan

@can('delete', $todo)
    <form action="{{ route('todos.destroy', $todo) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">削除</button>
    </form>
@endcan

@can('complete', $todo)
    <form action="{{ route('todos.complete', $todo) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success">完了にする</button>
    </form>
@endcan
```

### APIコントローラーでの使用例

```php
// app/Http/Controllers/Api/TodoController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Todo::class);
        
        $todos = Todo::where('user_id', auth()->id())->get();
        
        return response()->json($todos);
    }
    
    public function show(Todo $todo)
    {
        $this->authorize('view', $todo);
        
        return response()->json($todo);
    }
    
    public function update(Request $request, Todo $todo)
    {
        $this->authorize('update', $todo);
        
        $todo->update($request->validated());
        
        return response()->json($todo);
    }
    
    // 認可に失敗した場合は自動的に403レスポンスが返される
}
```

## 4. ポリシーの登録

```php
// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use App\Models\Todo;
use App\Models\Category;
use App\Policies\TodoPolicy;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Todo::class => TodoPolicy::class,
        Category::class => CategoryPolicy::class,
    ];
    
    public function boot()
    {
        $this->registerPolicies();
        
        // 追加のゲート定義があればここに記述
    }
}
```

## 5. テスト方法

### ポリシーのユニットテスト

```php
// tests/Unit/Policies/TodoPolicyTest.php
namespace Tests\Unit\Policies;

use App\Models\Todo;
use App\Models\User;
use App\Policies\TodoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoPolicyTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_can_view_own_todo()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        
        $policy = new TodoPolicy();
        
        $this->assertTrue($policy->view($user, $todo));
    }
    
    /** @test */
    public function user_cannot_view_others_todo()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        
        $policy = new TodoPolicy();
        
        $this->assertFalse($policy->view($user, $todo));
    }
    
    /** @test */
    public function admin_can_view_any_todo()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        
        $policy = new TodoPolicy();
        
        $this->assertTrue($policy->view($admin, $todo));
    }
}
```

### 機能テスト

```php
// tests/Feature/TodoControllerTest.php
namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function user_can_view_own_todos()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)->get(route('todos.index'));
        
        $response->assertStatus(200);
        $response->assertSee($todo->title);
    }
    
    /** @test */
    public function user_cannot_view_others_todo()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        
        $response = $this->actingAs($user)->get(route('todos.show', $todo));
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function user_cannot_update_others_todo()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        
        $response = $this->actingAs($user)->put(route('todos.update', $todo), [
            'title' => 'Updated Title',
        ]);
        
        $response->assertStatus(403);
        $this->assertDatabaseMissing('todos', [
            'id' => $todo->id,
            'title' => 'Updated Title',
        ]);
    }
}
```

## 6. ミドルウェアとポリシーの使い分け

ミドルウェアとポリシーの適切な使い分けについて検討した結果、認可ロジックは**ポリシーに統一**し、以下のような役割分担を行います：

### ミドルウェアの役割
- **認証チェック**：`auth`ミドルウェアでログイン状態の確認
- **CSRF保護**：`VerifyCsrfToken`ミドルウェア
- **リクエスト前処理**：リクエストデータの加工など

### ポリシーの役割
- **リソースアクセス権限**：モデルに対する操作権限の制御
- **所有権チェック**：ユーザーとリソースの関係性に基づく権限
- **機能アクセス制御**：特定機能の利用可否判断

この方針により、認可ロジックがポリシーに集約され、アプリケーション全体で一貫した権限管理を実現できます。

## 7. 選択理由

### ポリシー中心アプローチの利点

1. **モデル中心の設計**：
   - ビジネスドメインとの整合性が高い
   - 各モデルの権限ロジックが一箇所に集約される

2. **テスタビリティ**：
   - ポリシークラスは単体テストが容易
   - モックなしで権限ロジックをテスト可能

3. **フレームワーク統合**：
   - Laravelの標準機能として十分に統合されている
   - ビューとの連携が自然（`@can`ディレクティブ）

4. **コードの明示性**：
   - 権限チェックが明示的で追跡しやすい
   - 新規開発者も理解しやすい

5. **拡張性**：
   - 新しいアクションタイプの追加が容易
   - モデルごとに特化した権限ロジックを実装可能

この設計により、TODOアプリの拡張性と保守性を高め、セキュリティを確保しながら機能開発を進められます。 