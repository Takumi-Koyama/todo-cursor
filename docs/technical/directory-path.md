# Laravelのディレクトリ構造設計

## 設計原則

TODOアプリのディレクトリ構造は以下の原則と具体的実装方針に基づいて設計します：

### 基本設計原則
- ドメイン中心の設計: 機能ではなく、ビジネスドメインに基づいた分割
- 責務の明確な分離: 各ディレクトリは明確な役割を持つ
- 階層的構造: 関連するコードを適切な階層で管理
- 命名規則の一貫性: ディレクトリ名とファイル名の規則統一
- 探しやすさの重視: 直感的に探せる構造

### ハイブリッドアプローチの採用

Laravelの標準ディレクトリ構造を基本としながら、ドメイン駆動設計の考え方を取り入れたハイブリッドアプローチを採用します。

#### 標準Laravel構造
- `app/Http/Controllers`: コントローラー
- `app/Models`: Eloquentモデル
- `resources/views`: ビューファイル

#### ドメイン指向構造
- `app/Domains/Todo`: TODO関連のコード
- `app/Domains/User`: ユーザー関連のコード
- `app/Domains/Category`: カテゴリー関連のコード

### 具体的な設計方針

#### ディレクトリ構造
```
app/
├── Console/                    # コマンド
├── Domains/                    # ドメイン別のコード
│   ├── Todo/                   # TODOドメイン
│   │   ├── Controllers/        # コントローラー
│   │   ├── Events/             # イベント
│   │   ├── Exceptions/         # 例外
│   │   ├── Models/             # Eloquentモデル
│   │   ├── Notifications/      # 通知
│   │   ├── Repositories/       # リポジトリ
│   │   ├── Requests/           # フォームリクエスト
│   │   ├── Resources/          # APIリソース
│   │   └── Services/           # サービス
│   └── User/                   # ユーザードメイン
│       └── ...                 # 同様の構造
├── Http/                       # HTTPリクエスト関連
│   ├── Controllers/            # 汎用コントローラー
│   └── Middleware/             # ミドルウェア
├── Providers/                  # サービスプロバイダ
└── Support/                    # ヘルパーと汎用クラス
```

#### 命名規則
- ディレクトリ名: 単数形、パスカルケース `Todo`
- ファイル名: 単数形、パスカルケース `TodoController.php`
- 名前空間: PSR-4に準拠 `App\Domains\Todo\Controllers`

#### ドメイン分離の基準
- 独立したビジネス概念: Todo、User、Categoryなど
- 十分な複雑さと規模: 少なくとも複数のクラスが必要なもの
- 明確な境界: 他のドメインとの依存関係が少ないもの 