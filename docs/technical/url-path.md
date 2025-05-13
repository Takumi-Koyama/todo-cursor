# Laravelのパス設計

## 設計原則

TODOアプリのパス設計は以下の原則と具体的実装方針に基づいて行います：

### 基本設計原則
- リソース指向の設計: 各エンティティを独立したリソースとして捉える
- 階層的構造: 基本は浅い階層で、関連性が強い場合のみネスト
- 命名規則の一貫性: 複数形・単数形の使い分けと統一
- 意味的明確さ: URLを見るだけで何のリソースにアクセスするか理解できる
- ドメイン駆動設計の反映: ビジネスドメインの概念をURLに反映

### ハイブリッドアプローチの採用

リソースベースの設計を基本としながら、特定のユースケースではアクション指向のパスを組み合わせるハイブリッドアプローチを採用します。

#### リソース指向パス（RESTful）
- 基本的なCRUD操作: `/todos`, `/categories`, `/tags`
- リソース関連付け: `/todos/{todo_id}/tags`

#### アクション指向パス
- 特殊なユースケース: `/todos/reorder`, `/todos/complete-all`
- 複雑な検索・フィルタリング: `/todos/search`, `/todos/filter`

#### ドメイン指向パス
- ドメイン特有の概念: `/dashboard`, `/statistics`
- ユーザーフロー: `/onboarding`, `/wizard`

### 具体的な設計方針

#### URL構造
- 基本的にはフラットな構造を採用: `/todos`, `/categories`, `/tags`
- 必要に応じて最大1階層のネスト構造: `/categories/{category_id}/todos`
- アクション指向のエンドポイントは動詞を使用: `/todos/reorder`, `/todos/search`

#### 命名規則
- リソースコレクションは複数形: `/todos`, `/categories`
- 単一リソースはリソース名の複数形+ID: `/todos/{todo_id}`
- IDパラメータはリソース名を修飾: 単なる`{id}`ではなく`{todo_id}`や`{category_id}`
- アクションは動詞または目的を表す名詞

#### URL形式
- Webアプリケーションのルートには kebab-case を使用: `/user-profiles`, `/completed-todos`
- API応答のJSONプロパティにはcamelCaseを使用
- データベース連携にはsnake_caseを使用（Eloquentモデルとの連携のため）

#### ドメイン分離とバージョニング
- 機能に基づく分離: `/users/*`, `/todos/*`, `/settings/*`
- APIバージョニングが必要な場合はURLパスによるバージョニングを採用: `/api/v1/todos` 