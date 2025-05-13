# APIレスポンス設計

## 概要

本プロジェクトでは、APIレスポンスの一貫性と柔軟性を確保するため、Laravelの標準機能である`JsonResource`クラスを使用します。これにより、モデルデータをJSON形式に変換する際の構造を統一し、必要に応じて適切な変換やフィルタリングを行うことができます。

## 基本方針

1. すべてのAPIレスポンスには`JsonResource`を使用する
2. コレクションは基本的に`Resource::collection()`メソッドを使用する
3. 各モデルに対応するリソースクラスを作成する
4. レスポンス構造は一貫したフォーマットに統一する
5. 複合データの返却には専用のリソースクラスを作成する

## リソースクラスの作成方法

リソースクラスは以下のコマンドで作成できます：

```bash
# 単一リソースの作成
php artisan make:resource TodoResource
```

## 実装パターン

### 基本的なリソースクラス

単一モデルに対するリソースクラスの基本構造：

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_completed' => (bool) $this->is_completed,
            'due_date' => $this->due_date,
            'priority' => $this->priority,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => UserResource::make($this->user),
        ];
    }
}
```

## コントローラーでの使用方法

### 単一リソースの返却

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TodoResource;
use App\Models\Todo;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function show(Todo $todo)
    {
        return TodoResource::make($todo);
    }
}
```

### コレクションの返却

```php
public function index()
{
    $todos = Todo::all();
    
    // 単一リソースのcollectionメソッドを使用
    return TodoResource::collection($todos);
}
```

### ページネーション対応

```php
public function index()
{
    $todos = Todo::paginate(15);
    return TodoResource::collection($todos);
    
    // ページネーション情報は自動的に含まれます
}
```

## 複合データの返却

複数の異なるタイプのデータを一つのレスポンスとして返す場合の実装方法。

### 複合データ用のリソースクラス

```php
// App\Http\Resources\TodoDashboardResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoDashboardResource extends JsonResource
{
    protected $todos;
    protected $stats;
    protected $categories;

    public function __construct($todos, $stats, $categories)
    {
        parent::__construct(null); // リソース自体は特定のモデルに紐づかない
        $this->todos = $todos;
        $this->stats = $stats;
        $this->categories = $categories;
    }

    public function toArray(Request $request): array
    {
        return [
            'todos' => TodoResource::collection($this->todos),
            'statistics' => [
                'total_count' => $this->stats['total_count'],
                'completed_count' => $this->stats['completed_count'],
                'overdue_count' => $this->stats['overdue_count'],
            ],
            'categories' => $this->categories,
            'last_updated_at' => now(),
        ];
    }
}
```

### 複合データを返すコントローラー

```php
// App\Http\Controllers\Api\TodoDashboardController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TodoDashboardResource;
use App\Models\Todo;
use App\Models\Category;
use Illuminate\Http\Request;

class TodoDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Todoデータ取得
        $todos = Todo::where('user_id', auth()->id())->get();
        
        // 統計データ計算
        $stats = [
            'total_count' => $todos->count(),
            'completed_count' => $todos->where('is_completed', true)->count(),
            'overdue_count' => $todos->where('due_date', '<', now())
                                    ->where('is_completed', false)
                                    ->count(),
        ];
        
        // カテゴリーデータ取得
        $categories = Category::all();
        
        // 複合データをリソースで返却
        return new TodoDashboardResource($todos, $stats, $categories);
    }
}
```
```

## エラーレスポンスの形式

エラーレスポンスも一貫した形式で返すことが重要です：

```php
// 404エラーの例
return response()->json([
    'message' => 'Todo not found',
    'errors' => [
        'todo' => ['The requested todo does not exist.']
    ]
], 404);

// バリデーションエラーの例
// FormRequestでは自動的にフォーマットされます
```
