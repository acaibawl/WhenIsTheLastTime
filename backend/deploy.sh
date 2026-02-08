#!/bin/bash
# =============================================================================
# WhenIsTheLastTime - バックエンド デプロイスクリプト
# Lambda + Bref へのデプロイ
#
# ローカルの PHP バージョンに依存せず、Docker コンテナ (PHP 8.4) を使って
# composer install / artisan コマンドを実行します。
# =============================================================================

set -e

STAGE=${1:-dev}
# プロジェクトルート（docker-compose.yml がある場所）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "=========================================="
echo " WITLT Backend - Deploy to AWS Lambda"
echo " Stage: $STAGE"
echo "=========================================="

cd "$PROJECT_ROOT"

# .env.{stage} ファイルの存在チェック
ENV_FILE="$SCRIPT_DIR/.env.$STAGE"
if [ ! -f "$ENV_FILE" ]; then
    echo ""
    echo "ERROR: $ENV_FILE が見つかりません。"
    echo "  cp backend/.env.deploy.example backend/.env.$STAGE"
    echo "として作成し、本番環境の値を設定してください。"
    exit 1
fi
echo "Using env file: $ENV_FILE"

# Docker コンテナが起動しているか確認
if ! docker compose ps --status running php | grep -q witlt_php; then
    echo ""
    echo "ERROR: php コンテナが起動していません。"
    echo "  docker compose up -d php"
    echo "を実行してから再度デプロイしてください。"
    exit 1
fi

# 1. 本番用の依存関係をインストール（dev 除外）
echo ""
echo "[1/4] Installing production dependencies..."
docker compose exec -T php composer install --no-dev --optimize-autoloader --no-interaction

# 2. キャッシュをクリア（ローカル環境のキャッシュが残らないように）
echo ""
echo "[2/4] Clearing caches..."
docker compose exec -T php php artisan config:clear
docker compose exec -T php php artisan route:clear
docker compose exec -T php php artisan view:clear

# 3. デプロイ
echo ""
echo "[3/4] Deploying to AWS Lambda (stage: $STAGE)..."
cd "$SCRIPT_DIR"
serverless deploy --stage "$STAGE"

# 4. dev 依存関係を復元（ローカル開発用）
echo ""
echo "[4/4] Restoring dev dependencies..."
cd "$PROJECT_ROOT"
docker compose exec -T php composer install

echo ""
echo "=========================================="
echo " Deploy completed!"
echo "=========================================="
echo ""
echo "Useful commands:"
echo "  # Run artisan command on Lambda:"
echo "  cd backend && serverless bref:cli --stage $STAGE --args=\"migrate --force\""
echo ""
echo "  # View logs:"
echo "  cd backend && serverless logs -f api --stage $STAGE --tail"
echo ""
