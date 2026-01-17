FROM node:22-alpine

# 作業ディレクトリ設定
WORKDIR /app

# プロジェクト初期化のため、ファイルコピーとインストールはスキップ
# COPY package*.json ./
# RUN npm install
# COPY . .

# ポート3000を公開
EXPOSE 3000

# プロジェクト初期化のため、コンテナを起動したままにする
CMD ["npm", "run", "dev"]
