# TODOの並び替え実装方法

TODOアイテムのドラッグ&ドロップによる並び替え機能の実装方法について検討しました。特に大量のTODOアイテムがある場合に効率的に並び替えを行う方法について検討しています。

## 1. 実装方針（結論）

TODOの並び替えには「**変更されたアイテムのみを更新する方法**」を採用します。

```php
public function reorder(Request $request)
{
    $request->validate([
        'movedItemId' => 'required|exists:todos,id',
        'newPosition' => 'required|integer|min:0'
    ]);
    
    $todo = Todo::findOrFail($request->movedItemId);
    $oldPosition = $todo->sort_order;
    $newPosition = $request->newPosition;
    
    // トランザクションで一括更新
    DB::transaction(function () use ($todo, $oldPosition, $newPosition) {
        if ($oldPosition > $newPosition) {
            // 上に移動: その間のアイテムを下にシフト
            Todo::where('user_id', auth()->id())
                ->where('sort_order', '>=', $newPosition)
                ->where('sort_order', '<', $oldPosition)
                ->increment('sort_order');
        } else {
            // 下に移動: その間のアイテムを上にシフト
            Todo::where('user_id', auth()->id())
                ->where('sort_order', '>', $oldPosition)
                ->where('sort_order', '<=', $newPosition)
                ->decrement('sort_order');
        }
        
        // 移動したアイテム自体を更新
        $todo->sort_order = $newPosition;
        $todo->save();
    });
    
    return response()->json(['success' => true]);
}
```

**フロントエンド側の実装**:

```javascript
// 並び替え完了時の処理
function onDragEnd(event) {
    const itemId = event.item.dataset.id;
    const newPosition = Array.from(event.item.parentNode.children).indexOf(event.item);
    
    // バックエンドに並び順の更新をリクエスト
    fetch('/api/todos/reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            movedItemId: itemId,
            newPosition: newPosition
        })
    });
}
```

## 2. 検討内容

TODOの並び替え機能を実装するにあたり、以下の3つの方法を検討しました。

### 方法1: 変更されたアイテムのみを更新する方法

この方法では、アイテムが移動した際に、影響を受けるアイテムのみを効率的に更新します。

```php
public function reorder(Request $request)
{
    $request->validate([
        'movedItemId' => 'required|exists:todos,id',
        'newPosition' => 'required|integer|min:0'
    ]);
    
    $todo = Todo::findOrFail($request->movedItemId);
    $oldPosition = $todo->sort_order;
    $newPosition = $request->newPosition;
    
    // 1回のクエリで、影響を受けるアイテムのみ一括更新
    if ($oldPosition > $newPosition) {
        // 上に移動: その間のアイテムを下にシフト
        Todo::where('user_id', auth()->id())
            ->where('sort_order', '>=', $newPosition)
            ->where('sort_order', '<', $oldPosition)
            ->increment('sort_order');
    } else {
        // 下に移動: その間のアイテムを上にシフト
        Todo::where('user_id', auth()->id())
            ->where('sort_order', '>', $oldPosition)
            ->where('sort_order', '<=', $newPosition)
            ->decrement('sort_order');
    }
    
    // 移動したアイテム自体を更新
    $todo->sort_order = $newPosition;
    $todo->save();
    
    return response()->json(['success' => true]);
}
```

### 方法2: CASE文を使った一括更新

SQL文のCASE文を使用して、1回のクエリですべての更新を行う方法。

```php
public function reorder(Request $request)
{
    // items: [{id: 1, order: 3}, {id: 2, order: 1}, ...]
    $items = $request->items;
    $cases = [];
    $ids = [];
    
    foreach ($items as $item) {
        $cases[] = "WHEN {$item['id']} THEN {$item['order']}";
        $ids[] = $item['id'];
    }
    
    $ids = implode(',', $ids);
    $caseStatement = implode(' ', $cases);
    
    // 1回のクエリですべての更新を実行
    DB::update("UPDATE todos SET sort_order = CASE id {$caseStatement} END WHERE id IN ({$ids}) AND user_id = ?", [auth()->id()]);
    
    return response()->json(['success' => true]);
}
```

### 方法3: 離散的な値を使用する方法

各TODOアイテムのsort_orderを離散的な値（例: 100, 200, 300...）にして、中間に新しい値を挿入できるようにする方法。

```php
// 新規TODO作成時: 既存の最大値+1000として保存
// これにより、ドラッグ&ドロップ操作中に間に挿入する余地ができる

// ときどき整理する: 数値が大きくなりすぎたら、1〜100などに正規化
public function normalizeOrders()
{
    $todos = Todo::where('user_id', auth()->id())
                 ->orderBy('sort_order')
                 ->get();
    
    DB::transaction(function() use ($todos) {
        foreach($todos as $index => $todo) {
            $todo->sort_order = ($index + 1) * 100;
            $todo->save();
        }
    });
}
```

## 3. 選択理由

「**変更されたアイテムのみを更新する方法**」を選んだ理由は以下の通りです：

1. **効率性**: 2〜3回のクエリで処理が完了し、パフォーマンスに優れている
2. **実装の簡潔さ**: コードが理解しやすく、メンテナンスも容易
3. **局所的な変更**: 影響範囲が限定され、データ整合性のリスクが低い
4. **拡張性**: 将来的な要件変更にも対応しやすい設計

### 他の方法を選ばなかった理由

- **CASE文を使った一括更新**: SQLインジェクションのリスクがあり、大量のアイテムがある場合にクエリが複雑になる
- **離散的な値を使用する方法**: 長期的な運用で値が大きくなりすぎる可能性があり、定期的な正規化が必要

### 使用例

例えば、100個のTODOアイテムがあり、最下位（sort_order = 100）のアイテムを最上位（sort_order = 1）に移動する場合：

1. 移動するアイテム（ID: 100）を取得
2. 影響を受けるアイテム（sort_order: 1〜99）を1つずつ下にシフト
3. 移動するアイテムのsort_orderを1に設定

全体で3回のクエリのみで処理が完了します。これにより、ユーザー体験を損なうことなく効率的なTODOの並び替えが実現できます。 