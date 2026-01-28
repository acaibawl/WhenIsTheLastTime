# GitHub Copilot Instructions - WhenIsTheLastTime

## プロジェクト概要

「最後はいつ？」(When Is The Last Time) は、日常生活におけるイベント（活動）を記録し、最後に実行してからの経過時間を追跡・管理するWebアプリケーションです。

## 技術スタック

### バックエンド
- **言語**: PHP 8.2+
- **フレームワーク**: Laravel 12.x
- **認証**: JWT Authentication (tymon/jwt-auth)
- **データベース**: MySQL 8.4
- **キャッシュ**: Redis 8.2
- **開発ツール**: 
  - PHPStan (静的解析)
  - Laravel Pint (コードフォーマッタ)
  - PHPUnit (テスト)
  - Laravel IDE Helper

### フロントエンド
- **フレームワーク**: Nuxt 4.x
- **UI**: Nuxt UI 4.x
- **言語**: TypeScript
- **開発ツール**:
  - ESLint
  - Vue TSC (型チェック)

### インフラ
- **コンテナ**: Docker + Docker Compose
- **Webサーバー**: Nginx (Alpine)
- **メール送信テスト**: Mailpit

## Docker コンテナ構成

| コンテナ名 | サービス | 用途 |
|----------|---------|------|
| witlt_nginx | nginx | リバースプロキシ |
| witlt_php | php | Laravel アプリケーション |
| witlt_frontend | frontend | Nuxt アプリケーション |
| witlt_db | db | MySQL データベース |
| witlt_redis | redis | キャッシュ・セッション管理 |
| witlt_mailpit | mailpit | メール送信テスト |

## 重要な規則

### コマンド実行ルール

#### バックエンド（PHP/Laravel）関連のコマンドは `php` コンテナで実行
```bash
# 例: Composer コマンド
docker compose exec php composer install
docker compose exec php composer update

# 例: Artisan コマンド
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
docker compose exec php php artisan test
docker compose exec php php artisan tinker

# 例: コード品質チェック
docker compose exec php composer pint
docker compose exec php composer analyse
docker compose exec php php artisan test
```

#### フロントエンド（Node.js/Nuxt）関連のコマンドは `frontend` コンテナで実行
```bash
# 例: npm/pnpm コマンド
docker compose exec frontend npm install
docker compose exec frontend npm run dev
docker compose exec frontend npm run build

# 例: Nuxt コマンド
docker compose exec frontend npm run typecheck
docker compose exec frontend npm run lint
```

### ディレクトリ構造

```
/home/s_sato/workspace/WhenIsTheLastTime/
├── backend/              # Laravel アプリケーション
│   ├── app/
│   │   ├── Http/
│   │   ├── Models/      # Eloquent モデル
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/  # データベースマイグレーション
│   │   ├── factories/   # モデルファクトリ
│   │   └── seeders/     # シーダー
│   ├── routes/
│   │   └── api.php     # API ルート定義
│   └── tests/
├── frontend/            # Nuxt アプリケーション
│   ├── app/
│   │   ├── components/ # Vue コンポーネント
│   │   ├── pages/      # ページコンポーネント
│   │   └── assets/     # 静的アセット
│   └── nuxt.config.ts  # Nuxt 設定
├── container/           # Docker 関連ファイル
│   ├── php/
│   ├── frontend/
│   ├── nginx/
│   └── mysql/
└── doc/                # プロジェクト仕様書
```

### データモデル

主要なモデル：
- **User**: ユーザー情報
- **Event**: 追跡対象のイベント
- **History**: イベント実行履歴
- **UserSetting**: ユーザー設定

### コーディング規約

#### PHP/Laravel
- **PSR-12** に準拠（Laravel Pint で自動整形）
- **PHPStan Level 5** での静的解析に合格すること
- 型宣言を積極的に使用
- Eloquent モデルには PHPDoc を適切に記述
- API レスポンスは JSON 形式で返却
- バリデーションは FormRequest クラスを使用
- **データベーストランザクション**: `DB::transaction()` のクロージャー形式ではなく、`DB::beginTransaction()`、`DB::commit()`、`DB::rollBack()` を使用すること
  ```php
  DB::beginTransaction();
  try {
      // データベース操作
      DB::commit();
  } catch (\Throwable $e) {
      DB::rollBack();
      // エラーハンドリング
  }
  ```

#### TypeScript/Vue
- **Strict モード** を有効化
- Composition API を使用
- コンポーネントは単一責任の原則に従う
- Props と Emits には型定義を必須とする

### API 設計

- RESTful API として設計
- 認証には JWT トークンを使用
- エンドポイントのプレフィックス: `/api/`
- レスポンス形式は統一されたJSON構造
- **重要**: フロントエンドからバックエンドAPIを呼び出す際は、`/api` プレフィックスなしでURLを指定すること（例: `/events` であり `/api/events` ではない）

### 開発フロー

1. **開発環境の起動**
   ```bash
   docker compose up -d
   ```

2. **バックエンドの初期セットアップ**
   ```bash
   docker compose exec php composer install
   docker compose exec php php artisan key:generate
   docker compose exec php php artisan migrate
   docker compose exec php php artisan db:seed
   ```

3. **フロントエンドの初期セットアップ**
   ```bash
   docker compose exec frontend npm install
   ```

4. **開発サーバーへのアクセス**
   - フロントエンド: http://localhost:3000
   - バックエンド API: http://localhost:80/api
   - Mailpit Web UI: http://localhost:8025

### テスト実行

#### バックエンド
```bash
# PHPUnit テスト実行
docker compose exec php php artisan test

# 特定のテストファイル実行
docker compose exec php php artisan test --filter=EventTest

# カバレッジ付きテスト
docker compose exec php php artisan test --coverage
```

#### フロントエンド
```bash
# 型チェック
docker compose exec frontend npm run typecheck

# Lint チェック
docker compose exec frontend npm run lint
```

### よく使うコマンド

#### データベース操作
```bash
# マイグレーション実行
docker compose exec php php artisan migrate

# マイグレーションのロールバック
docker compose exec php php artisan migrate:rollback

# データベースのリフレッシュとシード
docker compose exec php php artisan migrate:fresh --seed

# tinker（REPL）起動
docker compose exec php php artisan tinker
```

#### キャッシュクリア
```bash
docker compose exec php php artisan cache:clear
docker compose exec php php artisan config:clear
docker compose exec php php artisan route:clear
docker compose exec php php artisan view:clear
```

#### コード生成
```bash
# Controller 生成
docker compose exec php php artisan make:controller EventController

# Model 生成（マイグレーション付き）
docker compose exec php php artisan make:model Event -m

# FormRequest 生成
docker compose exec php php artisan make:request StoreEventRequest

# Factory 生成
docker compose exec php php artisan make:factory EventFactory
```

## プロジェクト固有の注意事項

1. **日本語対応**: UI は日本語で提供され、`lang/ja` ディレクトリに翻訳ファイルが配置されています

2. **タイムゾーン**: アプリケーションのタイムゾーンは `Asia/Tokyo` です

3. **コンテナ間通信**: 各サービスは `witlt_network` で接続されており、サービス名で相互参照可能です

4. **ポート番号**:
   - HTTP: 80
   - HTTPS: 443
   - Frontend Dev: 3000
   - MySQL: 3306
   - Redis: 16379
   - Mailpit SMTP: 1025
   - Mailpit Web UI: 8025

5. **環境変数**: `.env` ファイルで環境固有の設定を管理します（Git 管理外）

## コード生成時の推奨事項

- Laravel のベストプラクティスに従う
- セキュリティを常に考慮（SQL インジェクション、XSS、CSRF 対策）
- エラーハンドリングを適切に実装
- ログ出力を適切に行う
- パフォーマンスを考慮したクエリを記述（N+1 問題の回避）
- テストコードも併せて生成する
- API ドキュメントのコメントを記述する
- アクセシビリティを考慮した UI 実装
