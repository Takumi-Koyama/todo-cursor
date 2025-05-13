# TODOの並び替え実装方法

## 実装方針

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

## 使用例

例えば、100個のTODOアイテムがあり、最下位（sort_order = 100）のアイテムを最上位（sort_order = 1）に移動する場合：

1. 移動するアイテム（ID: 100）を取得
2. 影響を受けるアイテム（sort_order: 1〜99）を1つずつ下にシフト
3. 移動するアイテムのsort_orderを1に設定

全体で3回のクエリのみで処理が完了します。これにより、ユーザー体験を損なうことなく効率的なTODOの並び替えが実現できます。 