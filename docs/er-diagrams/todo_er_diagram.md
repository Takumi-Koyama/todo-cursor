# TODOアプリ ER図

```mermaid
erDiagram
    users {
        bigint id PK "NOT NULL"
        string name "NOT NULL"
        string email UK "NOT NULL"
        string password "NOT NULL"
        timestamp email_verified_at "NULL"
        string remember_token "NULL"
    }
    
    todos {
        bigint id PK "NOT NULL"
        bigint user_id FK "NOT NULL"
        bigint category_id FK "NULL"
        string title "NOT NULL"
        text description "NULL"
        date due_date "NULL"
        boolean is_completed "NOT NULL"
        int priority "NULL"
        int sort_order "NOT NULL"
    }
    
    categories {
        bigint id PK "NOT NULL"
        bigint user_id FK "NOT NULL"
        string name "NOT NULL"
        string color "NULL"
    }
    
    tags {
        bigint id PK "NOT NULL"
        bigint user_id FK "NOT NULL"
        string name "NOT NULL"
        string color "NULL"
    }
    
    todo_tag {
        bigint todo_id FK "NOT NULL"
        bigint tag_id FK "NOT NULL"
    }
    
    users ||--o{ todos : "has"
    users ||--o{ categories : "has"
    users ||--o{ tags : "has"
    categories ||--o{ todos : "categorizes"
    todos ||--o{ todo_tag : "has"
    tags ||--o{ todo_tag : "belongs to"
```

## テーブル定義説明

### users テーブル
ユーザー情報を管理するテーブル。Laravel標準の認証システムを使用。
- `id`: 主キー
- `name`: ユーザー名（必須）
- `email`: メールアドレス（必須、一意）
- `password`: パスワード（必須）
- `email_verified_at`: メール確認日時（任意）
- `remember_token`: ログイン状態保持用トークン（任意）

### todos テーブル
TODOタスク情報を管理するテーブル。
- `id`: 主キー
- `user_id`: タスクの所有者（必須）
- `category_id`: タスクのカテゴリー（任意）
- `title`: タスクのタイトル（必須）
- `description`: タスクの詳細説明（任意）
- `due_date`: 期限日（任意）
- `is_completed`: 完了フラグ（必須）
- `priority`: 優先度（任意、1-5など）
- `sort_order`: 表示順序（必須、ユーザーによる手動並び替え用）

### categories テーブル
タスクのカテゴリー情報を管理するテーブル。
- `id`: 主キー
- `user_id`: カテゴリーの所有者（必須）
- `name`: カテゴリー名（必須）
- `color`: カテゴリーの色（任意、HEXカラーコードなど）

### tags テーブル
タグ情報を管理するテーブル。複数のタスクに紐づけ可能。
- `id`: 主キー
- `user_id`: タグの所有者（必須）
- `name`: タグ名（必須）
- `color`: タグの色（任意、HEXカラーコードなど）

### todo_tag テーブル
TODOタスクとタグの多対多関係を管理する中間テーブル。
- `todo_id`: TODOタスクのID（必須）
- `tag_id`: タグのID（必須） 