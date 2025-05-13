# TODOアプリ ドキュメント

## ディレクトリ構成

```
docs/
├── er-diagrams/        # ER図関連
│   └── todo_er_diagram.md      # TODOアプリのER図
├── technical/          # 技術検討関連
│   ├── api-responses.md        # APIレスポンスの設計
│   ├── authentication.md       # 認証方法の検討
│   ├── authorization.md        # 認可の実装方針
│   ├── directory-path.md       # ディレクトリパス設計
│   ├── error-handling-logging.md  # エラー処理とロギングの検討
│   ├── model.md                # モデル設計
│   ├── todo-reordering.md      # TODOの並び替え実装の検討
│   ├── url-path.md             # URLパス設計
│   └── validation.md           # バリデーション実装方針
├── specifications/     # 仕様書関連
└── user-guides/        # ユーザーガイド関連
```

## ドキュメント一覧

### ER図
- [TODOアプリのER図](./er-diagrams/todo_er_diagram.md) - データベース設計とテーブル定義

### 技術検討
- [APIレスポンスの設計](./technical/api-responses.md) - APIレスポンス形式の標準化
- [認証方法の検討](./technical/authentication.md) - TODOアプリに最適な認証方法の選定
- [認可の実装方針](./technical/authorization.md) - アクセス制御と権限管理の実装方針
- [ディレクトリパス設計](./technical/directory-path.md) - プロジェクトのディレクトリ構造設計
- [エラー処理とロギング](./technical/error-handling-logging.md) - エラー処理、ロギング、監査ログの実装方針
- [モデル設計](./technical/model.md) - データモデルとスキーマ設計
- [TODOの並び替え実装](./technical/todo-reordering.md) - ドラッグ&ドロップによるTODOの並び替え実装方法の検討
- [URLパス設計](./technical/url-path.md) - アプリケーションのURLパス設計
- [バリデーション実装方針](./technical/validation.md) - 入力検証の実装方針 