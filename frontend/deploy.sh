#!/bin/bash
# =============================================================================
# WhenIsTheLastTime - フロントエンド デプロイスクリプト
# Lambda + Serverless へのデプロイ
#
# .env.{stage} から環境変数を読み込み、Nuxt ビルド → Serverless デプロイ
# を実行します。Docker コンテナ (frontend) を使用してビルドします。
# =============================================================================

set -e

STAGE=${1:-dev}
# プロジェクトルート（docker-compose.yml がある場所）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "=========================================="
echo " WITLT Frontend - Deploy to AWS Lambda"
echo " Stage: $STAGE"
echo "=========================================="

cd "$PROJECT_ROOT"

# .env.{stage} ファイルの存在チェック
ENV_FILE="$SCRIPT_DIR/.env.$STAGE"
if [ ! -f "$ENV_FILE" ]; then
    echo ""
    echo "ERROR: $ENV_FILE が見つかりません。"
    echo "  cp frontend/.env.deploy.example frontend/.env.$STAGE"
    echo "として作成し、本番環境の値を設定してください。"
    exit 1
fi
echo "Using env file: $ENV_FILE"

# .env ファイルから環境変数を読み込み（コメント行・空行を除外）
echo ""
echo "Loading environment variables from $ENV_FILE..."
set -a
while IFS='=' read -r key value; do
    # コメント行・空行をスキップ
    [[ -z "$key" || "$key" =~ ^# ]] && continue
    # 値の前後の空白とクォートを除去
    value="${value#\"}"
    value="${value%\"}"
    value="${value#\'}"
    value="${value%\'}"
    export "$key=$value"
done < "$ENV_FILE"
set +a

# Docker コンテナが起動しているか確認
if ! docker compose ps --status running frontend | grep -q witlt_frontend; then
    echo ""
    echo "ERROR: frontend コンテナが起動していません。"
    echo "  docker compose up -d frontend"
    echo "を実行してから再度デプロイしてください。"
    exit 1
fi

# 1. 依存関係をインストール
echo ""
echo "[1/4] Installing dependencies..."
docker compose exec -T frontend npm ci

# 2. Nuxt ビルド（環境変数をコンテナに渡す）
echo ""
echo "[2/4] Building Nuxt application..."
docker compose exec -T \
    -e NUXT_PUBLIC_API_BASE_URL="${NUXT_PUBLIC_API_BASE_URL}" \
    -e NUXT_PUBLIC_BASE_URL="${NUXT_PUBLIC_BASE_URL}" \
    frontend npm run build

# 3. デプロイ
echo ""
echo "[3/4] Deploying to AWS Lambda (stage: $STAGE)..."
cd "$SCRIPT_DIR"

# 環境変数を serverless にも渡す
NUXT_PUBLIC_API_BASE_URL="${NUXT_PUBLIC_API_BASE_URL}" \
NUXT_PUBLIC_BASE_URL="${NUXT_PUBLIC_BASE_URL}" \
serverless deploy --stage "$STAGE"

# 4. 完了
echo ""
echo "[4/4] Done!"

echo ""
echo "=========================================="
echo " Deploy completed!"
echo "=========================================="
echo ""
echo "Useful commands:"
echo "  # View logs:"
echo "  cd frontend && serverless logs -f nuxt --stage $STAGE --tail"
echo ""
