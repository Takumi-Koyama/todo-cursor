# バリデーション設計

## 概要

本プロジェクトでは、データの検証（バリデーション）にLaravelの標準機能である`FormRequest`クラスを使用します。これにより、コントローラーからバリデーションロジックを分離し、責務を明確に分けることが可能になります。

## 基本方針

1. 各リクエストに対応する`FormRequest`クラスを作成する
2. バリデーションルールは各`FormRequest`クラス内で明示的に定義する
3. カスタムバリデーションメッセージは当初は実装せず、必要に応じて後から追加する
4. 共通のバリデーションルールが多数発生した場合は、抽象化を検討する

## 実装パターン

### FormRequestクラスの基本構造

各FormRequestクラスでは以下のメソッドを実装します：

- `authorize()`: リクエストの認可チェック
- `rules()`: バリデーションルールの定義
- `attributes()`: 属性名のカスタマイズ（必要な場合）
- `prepareForValidation()`: バリデーション前のデータ前処理（必要な場合）
- `withValidator()`: 条件付きバリデーションの実装（必要な場合）

### prepareForValidationとwithValidatorの明確な使い分け

`prepareForValidation`と`withValidator`は異なるタイミングで実行され、異なる役割を持ちます。以下に明確な使い分けを示します：

#### prepareForValidationの役割

- **実行タイミング**: バリデーション**前**に実行
- **主な用途**: データの整形・正規化
- **実装すべき処理**:
  - 空文字列をnullに変換
  - 日付形式の統一・変換
  - 文字列の前後の空白除去（trim）
  - カンマ区切り文字列を配列に変換
  - 値の正規化（大文字小文字統一など）
  - バリデーション前の単純なデータ変換

#### withValidatorの役割

- **実行タイミング**: バリデーションルール適用**後**の検証段階
- **主な用途**: 複雑な条件付きバリデーション、関連データのチェック
- **実装すべき処理**:
  - 複数フィールド間の相関関係チェック
  - データベース参照を必要とする検証
  - 条件によるバリデーションルールの動的追加
  - 複雑なビジネスルールに基づく検証
  - バリデーション結果に基づく追加チェック

#### 選択指針

- データの**形式変換や整形**が目的 → `prepareForValidation`を使用
- 複数フィールドの**関連チェックや複雑な条件**が必要 → `withValidator`を使用

### 実装例

```php
namespace App\Http\Requests\Todo;

use App\Models\Todo;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTodoRequest extends FormRequest
{
    public function authorize()
    {
        $todo = $this->route('todo');
        return $this->user()->can('update', $todo);
    }

    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'タイトル',
            'description' => '説明',
            'due_date' => '期限日',
            'priority' => '優先度',
        ];
    }

    public function prepareForValidation()
    {
        // 例：空文字列をnullに変換
        $this->merge([
            'description' => $this->description === '' ? null : $this->description,
            'due_date' => $this->due_date === '' ? null : $this->due_date,
        ]);
    }
}
```

## コントローラーでの呼び出し方

### 基本的な使用方法

コントローラーメソッドの引数にFormRequestクラスを型指定するだけで、自動的にバリデーションが実行されます。バリデーションが失敗した場合は、自動的にリダイレクトまたはJSONレスポンスが返されます。

```php
namespace App\Http\Controllers;

use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Models\Todo;

class TodoController extends Controller
{
    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        // この時点でバリデーションと認可は完了している
        
        // バリデーション済みデータの取得
        $validatedData = $request->validated();
        
        // モデルの更新
        $todo->update($validatedData);
        
        return redirect()->route('todos.show', $todo);
    }
}
```

## データ前処理と条件付きバリデーション

### prepareForValidation

バリデーション実行前にリクエストデータを前処理するためのメソッドです。以下のような用途で使用します：

- 空文字列をnullに変換
- 日付形式の統一
- リクエストデータの正規化

### withValidator

条件に応じたバリデーションルールの追加や、バリデーション後の追加処理を実装するためのメソッドです：

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // バリデーション後の追加チェック
        if ($this->somethingElseIsInvalid()) {
            $validator->errors()->add('field', 'Something is wrong with this field!');
        }
    });
}
```

## バリデーションエラーのハンドリング

FormRequestクラスのバリデーションが失敗した場合、自動的にリダイレクトされ、エラーメッセージがセッションに保存されます。APIリクエストの場合は、422 Unprocessable Entityステータスコードと共にJSONレスポンスが返されます。

## カスタムバリデーションルール

プロジェクト固有の複雑なバリデーションルールが必要な場合は、Laravelのカスタムバリデーションルール機能を使用します：

```bash
php artisan make:rule CustomRule
```