# Laravelを使用したTODOアプリ

## 環境構築

このプロジェクトはDockerを使用して開発環境を構築しています。

### 必要な環境

- Docker
- Docker Compose

### セットアップ手順

1. リポジトリをクローン
```bash
git clone <リポジトリURL>
cd todo-app
```

2. 環境変数ファイルの準備
```bash
cp .env.example .env
```

3. Dockerコンテナの起動
```bash
docker compose up -d
```

4. 依存関係のインストール
```bash
docker compose exec app composer install
```

5. 権限の設定
```bash
docker compose exec app chmod -R 777 storage bootstrap/cache
```

6. アプリケーションキーの生成
```bash
docker compose exec app php artisan key:generate
```

7. マイグレーションの実行
```bash
docker compose exec app php artisan migrate
```

## 開発環境へのアクセス

- Webアプリケーション: http://localhost:8080
- データベース: localhost:33060 (MySQLクライアントから接続可能)

## 主なコマンド

- コンテナ起動: `docker compose up -d`
- コンテナ停止: `docker compose down`
- Laravelコマンド実行: `docker compose exec app php artisan <コマンド>`
- Composerコマンド実行: `docker compose exec app composer <コマンド>` 