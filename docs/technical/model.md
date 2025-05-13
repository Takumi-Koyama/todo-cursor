# モデル実装方針

## 概要

本プロジェクトでは、Laravelのモデルを一貫した方法で実装するために、以下の方針に従います。モデルは、データベースとのやり取りやビジネスロジックの中心となるため、明確な設計と実装が重要です。

## 基本方針

1. 各モデルは対応するテーブルを明確に表現する
2. 大量代入保護（$fillable）を適切に設定する
3. 属性のキャスト（$casts）を明示的に定義する
4. リレーションシップを明確に定義する
5. 頻繁に使用されるクエリはスコープとして定義する
6. 派生属性はアクセサとして定義する
7. 一貫したフォーマットや検証はミューテタとして定義する
8. モデルイベントはbootメソッドで処理する

## 実装パターン

### 1. fillable（大量代入で更新可能な属性）

#### 定義方法

```php
class Todo extends Model
{
    /**
     * 大量代入で代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'due_date',
        'is_completed',
        'priority',
        'user_id',
    ];
}
```

#### 使用例

```php
// コントローラーでの使用例
public function store(CreateTodoRequest $request)
{
    $todo = Todo::create($request->validated());
    
    return TodoResource::make($todo);
}

// 既存モデルの更新
public function update(UpdateTodoRequest $request, Todo $todo)
{
    $todo->update($request->validated());
    
    return TodoResource::make($todo);
}
```

### 2. casts（属性のキャスト）

#### 定義方法

```php
class Todo extends Model
{
    /**
     * 属性のキャスト
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'datetime',
        'priority' => 'integer',
        'settings' => 'array',
        'meta_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

#### 使用例

```php
$todo = Todo::find(1);

// booleanにキャストされるため、条件文で直接使用可能
if ($todo->is_completed) {
    // ...
}

// datetimeにキャストされるため、Carbonのメソッドが使用可能
if ($todo->due_date->isPast()) {
    // ...
}

// arrayにキャストされるため、配列として操作可能
$settings = $todo->settings;
$settings['show_notifications'] = true;
$todo->settings = $settings;
$todo->save();
```

### 3. relation（リレーション定義）

#### 定義方法

```php
class Todo extends Model
{
    /**
     * このTodoを所有するユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * このTodoに紐づくサブタスク
     */
    public function subtasks()
    {
        return $this->hasMany(Subtask::class);
    }
    
    /**
     * このTodoに付けられたタグ
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps()
            ->withPivot('added_by');
    }
}
```

#### 使用例

```php
// 関連データの取得
$user = $todo->user;
$subtasks = $todo->subtasks;

// Eagerロード
$todos = Todo::with(['user', 'subtasks'])->get();

// リレーションを使ったクエリ
$userTodos = User::find(1)->todos;

// リレーションを使った条件付きクエリ
$todoWithPendingSubtasks = Todo::whereHas('subtasks', function ($query) {
    $query->where('is_completed', false);
})->get();

// 関連モデルの作成
$todo->subtasks()->create([
    'title' => 'サブタスク1',
    'is_completed' => false,
]);

// 多対多関連の操作
$todo->tags()->attach($tagId);
$todo->tags()->detach($tagId);
$todo->tags()->sync([$tagId1, $tagId2]);
```

### 4. scope（クエリスコープ）

#### 定義方法

```php
class Todo extends Model
{
    /**
     * 完了済みのTodoを取得するスコープ
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }
    
    /**
     * 未完了のTodoを取得するスコープ
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }
    
    /**
     * 指定日までに期限のあるTodoを取得するスコープ
     */
    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }
    
    /**
     * 優先度でソートするスコープ
     */
    public function scopeOrderByPriority($query, $direction = 'desc')
    {
        return $query->orderBy('priority', $direction);
    }
}
```

#### 使用例

```php
// 単一スコープの使用
$completedTodos = Todo::completed()->get();
$pendingTodos = Todo::pending()->get();

// 複数スコープの連鎖
$highPriorityPendingTodos = Todo::pending()
    ->orderByPriority()
    ->get();

// パラメータ付きスコープ
$todaysTodos = Todo::dueBefore(now()->endOfDay())->get();

// スコープとその他のクエリの組み合わせ
$userPendingTodos = Todo::pending()
    ->where('user_id', auth()->id())
    ->orderBy('due_date')
    ->paginate(15);
```

### 5. アクセサ/ミューテタ（従来の形式）

#### 定義方法

```php
class Todo extends Model
{
    /**
     * タイトルを取得する際のアクセサ
     */
    public function getTitleAttribute($value)
    {
        return ucfirst($value);
    }
    
    /**
     * タイトルを設定する際のミューテタ
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = trim($value);
    }
    
    /**
     * 期日までの残り日数を計算するアクセサ
     */
    public function getDaysLeftAttribute()
    {
        if (!$this->due_date || $this->is_completed) {
            return null;
        }
        
        return $this->due_date->startOfDay()->diffInDays(now()->startOfDay());
    }
    
    /**
     * Todoが期限切れかどうかを判定するアクセサ
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->due_date || $this->is_completed) {
            return false;
        }
        
        return $this->due_date < now();
    }
}
```

#### 使用例

```php
// アクセサで加工されたデータを取得
$title = $todo->title; // 自動的に最初の文字が大文字になる

// 仮想属性（データベースに存在しない属性）へのアクセス
$daysLeft = $todo->days_left;
if ($todo->is_overdue) {
    // 期限切れの処理
}

// ミューテタを通した設定
$todo->title = "  新しいタスク  "; // 自動的にトリムされる
$todo->save();

// APIリソースにも自動的に含まれる
return TodoResource::make($todo); // days_left, is_overdueも含まれる
```

### 6. bootメソッド（モデルイベント処理）

#### 定義方法

```php
class Todo extends Model
{
    /**
     * モデルのブートメソッド
     */
    protected static function boot()
    {
        parent::boot();
        
        // 作成時に自動的に現在のユーザーIDを設定
        static::creating(function ($todo) {
            if (!$todo->user_id && auth()->check()) {
                $todo->user_id = auth()->id();
            }
        });
        
        // 完了時にcompleted_atを設定
        static::updating(function ($todo) {
            $original = $todo->getOriginal('is_completed');
            $current = $todo->is_completed;
            
            if ($original === false && $current === true) {
                $todo->completed_at = now();
            } elseif ($original === true && $current === false) {
                $todo->completed_at = null;
            }
        });
        
        // 削除時に関連するサブタスクも削除
        static::deleting(function ($todo) {
            $todo->subtasks()->delete();
        });
    }
}
```

#### 使用例

```php
// モデルイベントは自動的に発火するため、明示的な使用はなし
$todo = Todo::create([
    'title' => '新しいタスク',
    // user_idを指定しなくても、認証中のユーザーIDが自動設定される
]);

// 更新時もイベントが発火
$todo->is_completed = true;
$todo->save();
// completed_atが自動的に設定される

// 削除時も関連するサブタスクが自動削除される
$todo->delete();
```

## 結論

モデルは一貫したパターンで実装することにより、コードの可読性と保守性が向上します。上記の方針に従って実装することで、ビジネスロジックの明確な表現と効率的なデータベース操作が可能になります。

新しいモデルを作成する際は、これらのパターンを参考にしつつ、特定のドメインに合わせた拡張を検討してください。 