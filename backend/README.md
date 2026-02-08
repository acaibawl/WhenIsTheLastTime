# WhenIsTheLastTime - Backend

「最後はいつ？」バックエンド API（Laravel 12 + PHP 8.4）

## アーキテクチャ

AWS Lambda + [Bref](https://bref.sh/) を使用したサーバーレスデプロイ構成です。

| コンポーネント | 技術 |
|-------------|------|
| ランタイム | PHP 8.4 (Bref php-84-fpm) |
| フレームワーク | Laravel 12 |
| 認証 | JWT (tymon/jwt-auth) |
| デプロイツール | Serverless Framework v4 |
| アーキテクチャ | ARM64 (Graviton2) |
| リージョン | ap-northeast-1 (東京) |
| データベース | Amazon RDS (MySQL 8.x) |
| メール送信 | Amazon SES |
| キャッシュ | database ドライバ (Lambda ではファイルシステムが読み取り専用のため) |
| ログ | CloudWatch Logs (stderr 経由) |

### Lambda 関数構成

| 関数名 | ランタイム | 用途 |
|-------|-----------|------|
| `api` | php-84-fpm | Web API エンドポイント（Lambda 関数 URL 使用） |
| `artisan` | php-84-console | Artisan コマンド実行（マイグレーション等） |

---

## 前提条件

以下がインストール・設定済みであること：

- **Docker / Docker Compose** — PHP 8.4 コンテナ経由で composer / artisan を実行するため
- **Serverless Framework v4** — `npm install -g serverless`
- **AWS CLI** — 認証情報が設定済みであること
- **IAM ユーザー** — 必要な権限を持つユーザー（後述）

---

## IAM ポリシー設定

デプロイ用 IAM ユーザーに以下のポリシーをアタッチしてください。

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "CloudFormation",
            "Effect": "Allow",
            "Action": [
                "cloudformation:CreateStack",
                "cloudformation:UpdateStack",
                "cloudformation:DeleteStack",
                "cloudformation:DescribeStacks",
                "cloudformation:DescribeStackEvents",
                "cloudformation:DescribeStackResources",
                "cloudformation:GetTemplate",
                "cloudformation:ValidateTemplate",
                "cloudformation:ListStackResources"
            ],
            "Resource": "arn:aws:cloudformation:ap-northeast-1:*:stack/witlt-*/*"
        },
        {
            "Sid": "S3",
            "Effect": "Allow",
            "Action": [
                "s3:CreateBucket",
                "s3:DeleteBucket",
                "s3:GetBucketLocation",
                "s3:GetObject",
                "s3:ListBucket",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:PutBucketPolicy",
                "s3:GetBucketPolicy",
                "s3:PutBucketAcl",
                "s3:GetEncryptionConfiguration",
                "s3:PutEncryptionConfiguration"
            ],
            "Resource": [
                "arn:aws:s3:::witlt-*",
                "arn:aws:s3:::witlt-*/*"
            ]
        },
        {
            "Sid": "Lambda",
            "Effect": "Allow",
            "Action": [
                "lambda:CreateFunction",
                "lambda:UpdateFunctionCode",
                "lambda:UpdateFunctionConfiguration",
                "lambda:DeleteFunction",
                "lambda:GetFunction",
                "lambda:GetFunctionConfiguration",
                "lambda:ListVersionsByFunction",
                "lambda:PublishVersion",
                "lambda:CreateAlias",
                "lambda:DeleteAlias",
                "lambda:GetAlias",
                "lambda:InvokeFunction",
                "lambda:AddPermission",
                "lambda:RemovePermission",
                "lambda:TagResource",
                "lambda:UntagResource",
                "lambda:PutFunctionEventInvokeConfig",
                "lambda:CreateFunctionUrlConfig",
                "lambda:UpdateFunctionUrlConfig",
                "lambda:GetFunctionUrlConfig",
                "lambda:DeleteFunctionUrlConfig"
            ],
            "Resource": "arn:aws:lambda:ap-northeast-1:*:function:witlt-*"
        },
        {
            "Sid": "LambdaLayer",
            "Effect": "Allow",
            "Action": [
                "lambda:GetLayerVersion"
            ],
            "Resource": "arn:aws:lambda:*:*:layer:*"
        },
        {
            "Sid": "IAM",
            "Effect": "Allow",
            "Action": [
                "iam:CreateRole",
                "iam:DeleteRole",
                "iam:GetRole",
                "iam:PassRole",
                "iam:PutRolePolicy",
                "iam:DeleteRolePolicy",
                "iam:GetRolePolicy",
                "iam:AttachRolePolicy",
                "iam:DetachRolePolicy",
                "iam:TagRole",
                "iam:UntagRole"
            ],
            "Resource": "arn:aws:iam::*:role/witlt-*"
        },
        {
            "Sid": "CloudWatchLogs",
            "Effect": "Allow",
            "Action": [
                "logs:CreateLogGroup",
                "logs:DeleteLogGroup",
                "logs:DescribeLogGroups",
                "logs:CreateLogStream",
                "logs:DeleteLogStream",
                "logs:DescribeLogStreams",
                "logs:PutLogEvents",
                "logs:GetLogEvents",
                "logs:FilterLogEvents",
                "logs:TagResource",
                "logs:PutRetentionPolicy"
            ],
            "Resource": "arn:aws:logs:ap-northeast-1:*:log-group:/aws/lambda/witlt-*:*"
        }
    ]
}
```

---

## 環境変数の設定

デプロイ前に以下の環境変数を設定してください（シェルの `export` または `.env` ファイル等）。

| 変数名 | 説明 | 例 |
|-------|------|-----|
| `APP_KEY` | Laravel アプリケーションキー | `base64:xxxxx...` |
| `APP_URL` | バックエンド API の URL | `https://xxxxx.lambda-url.ap-northeast-1.on.aws` |
| `FRONTEND_URL` | フロントエンドの URL（CORS 用） | `https://xxxxx.lambda-url.ap-northeast-1.on.aws` |
| `DB_HOST` | RDS エンドポイント | `witlt.xxxxx.ap-northeast-1.rds.amazonaws.com` |
| `DB_PORT` | MySQL ポート | `3306` |
| `DB_DATABASE` | データベース名 | `witlt` |
| `DB_USERNAME` | DB ユーザー名 | `witlt_user` |
| `DB_PASSWORD` | DB パスワード | `xxxxxxxx` |
| `JWT_SECRET` | JWT 署名用シークレット | `xxxxxxxx` |
| `MAIL_FROM_ADDRESS` | SES 送信元メールアドレス | `noreply@example.com` |

### APP_KEY の生成

```bash
docker compose exec php php artisan key:generate --show
```

### JWT_SECRET の生成

```bash
docker compose exec php php artisan jwt:secret --show
```

---

## RDS (MySQL) のセットアップ

Lambda から RDS に接続するため、以下を設定してください。

1. **RDS インスタンスを作成**
   - エンジン: MySQL 8.x
   - インスタンスクラス: `db.t4g.micro`（最小構成）
   - パブリックアクセス: **有効**（VPC 外の Lambda から接続する場合）
     - または VPC Lambda + NAT Gateway 構成（コストが増加）

2. **セキュリティグループ**
   - インバウンド: MySQL/Aurora (3306) を許可
   - パブリックアクセスの場合は、ソース IP を制限することを推奨

3. **データベースとユーザーの作成**
   ```sql
   CREATE DATABASE witlt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'witlt_user'@'%' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON witlt.* TO 'witlt_user'@'%';
   FLUSH PRIVILEGES;
   ```

> **注意**: Lambda をパブリック（VPC 外）で実行する場合、RDS はパブリックアクセスを有効にする必要があります。セキュリティグループで接続元を制限してください。

---

## デプロイ手順

### 1. Docker コンテナの起動

```bash
docker compose up -d php
```

デプロイスクリプトは Docker コンテナ内の PHP 8.4 を使用して `composer` / `artisan` コマンドを実行します。

### 2. 環境変数の設定

```bash
export APP_KEY="base64:xxxxxxxxxxxxxxxx"
export APP_URL="https://xxxxx.lambda-url.ap-northeast-1.on.aws"
export FRONTEND_URL="https://xxxxx.lambda-url.ap-northeast-1.on.aws"
export DB_HOST="witlt.xxxxx.ap-northeast-1.rds.amazonaws.com"
export DB_PORT="3306"
export DB_DATABASE="witlt"
export DB_USERNAME="witlt_user"
export DB_PASSWORD="xxxxxxxx"
export JWT_SECRET="xxxxxxxx"
export MAIL_FROM_ADDRESS="noreply@example.com"
```

### 3. デプロイ実行

```bash
cd backend
chmod +x deploy.sh
./deploy.sh prod
```

`deploy.sh` は以下の処理を順番に実行します：

1. **本番用依存関係インストール** — `composer install --no-dev --optimize-autoloader`（Docker コンテナ内）
2. **キャッシュクリア** — `config:clear`, `route:clear`, `view:clear`（Docker コンテナ内）
3. **Serverless デプロイ** — `serverless deploy --stage prod`（ホストマシン）
4. **dev 依存関係の復元** — `composer install`（Docker コンテナ内、ローカル開発用に復元）

### 4. デプロイ後のマイグレーション

デプロイ完了後、Lambda 上でマイグレーションを実行します。

```bash
cd backend
serverless bref:cli --stage prod --args="migrate --force"
```

#### マイグレーション状態の確認

```bash
serverless bref:cli --stage prod --args="migrate:status"
```

#### シーダーの実行（初回デプロイ時のみ）

```bash
serverless bref:cli --stage prod --args="db:seed --force"
```

---

## デプロイ後の確認

### ヘルスチェック

```bash
curl https://<LAMBDA_FUNCTION_URL>/health
```

### ログの確認

```bash
# API 関数のログをリアルタイム表示
cd backend
serverless logs -f api --stage prod --tail

# Artisan 関数のログを表示
serverless logs -f artisan --stage prod
```

### Lambda 上で Artisan コマンドを実行

```bash
cd backend

# マイグレーション実行
serverless bref:cli --stage prod --args="migrate --force"

# マイグレーションのロールバック
serverless bref:cli --stage prod --args="migrate:rollback --force"

# キャッシュクリア
serverless bref:cli --stage prod --args="cache:clear"

# ルート一覧
serverless bref:cli --stage prod --args="route:list"
```

---

## フロントエンドとの接続

デプロイ後、Lambda 関数 URL が発行されます。フロントエンド側の環境変数を更新してください。

```bash
# フロントエンドの serverless.yml または環境変数で設定
NUXT_PUBLIC_API_BASE_URL=https://<BACKEND_LAMBDA_FUNCTION_URL>
```

> **重要**: フロントエンドからバックエンド API を呼び出す際は、`/api` プレフィックスなしで URL を指定してください（例: `/events`）。

---

## ステージ管理

| ステージ | 用途 | コマンド |
|---------|------|---------|
| `dev` | 開発・テスト | `./deploy.sh dev` |
| `prod` | 本番環境 | `./deploy.sh prod` |

ステージごとに独立した Lambda 関数・CloudFormation スタックが作成されます。

---

## トラブルシューティング

### コールドスタート

Lambda のコールドスタートにより、初回リクエストで 2〜5 秒の遅延が発生する場合があります。対策：

- メモリサイズを増やす（現在 512MB）
- Provisioned Concurrency を設定（追加コストあり）
- 定期的にヘルスチェックを送信してウォームアップ

### CORS エラー

`FRONTEND_URL` 環境変数が正しく設定されているか確認してください。`config/cors.php` で `allowed_origins` に `FRONTEND_URL` が使用されます。

### データベース接続エラー

- RDS のセキュリティグループで Lambda からの接続を許可しているか確認
- パブリックアクセスが有効か確認（VPC 外 Lambda の場合）
- 環境変数 `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` が正しいか確認

### デプロイスクリプトのエラー

- **「php コンテナが起動していません」** — `docker compose up -d php` を実行
- **IAM 権限エラー** — 上記の IAM ポリシーがアタッチされているか確認
- **PHP バージョンエラー** — Docker コンテナの PHP 8.4 が使用されるため、ローカルの PHP バージョンは無関係

---

## ローカル開発

ローカル開発は Docker Compose を使用します。詳細はプロジェクトルートの README を参照してください。

```bash
# 全コンテナの起動
docker compose up -d

# バックエンド初期セットアップ
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed

# テスト実行
docker compose exec php php artisan test

# 静的解析
docker compose exec php composer analyse

# コードフォーマット
docker compose exec php composer pint
```

---

## 関連ファイル

| ファイル | 説明 |
|--------|------|
| `serverless.yml` | Serverless Framework 設定（Lambda 関数定義） |
| `deploy.sh` | デプロイスクリプト |
| `composer.json` | PHP 依存関係（bref/bref, bref/laravel-bridge 含む） |
| `routes/api.php` | API ルート定義 |
| `config/cors.php` | CORS 設定 |
