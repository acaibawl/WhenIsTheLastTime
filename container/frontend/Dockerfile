FROM node:22-alpine

# 作業ディレクトリ設定
WORKDIR /app

# プロジェクト初期化のため、ファイルコピーとインストールはスキップ
# COPY package*.json ./
# RUN npm install
# COPY . .

RUN apk add --no-cache ca-certificates
COPY when_is_the_last_time.local.crt /usr/local/share/ca-certificates/when_is_the_last_time.local.crt
RUN update-ca-certificates

# ポート3000を公開
EXPOSE 3000

# プロジェクト初期化のため、コンテナを起動したままにする
CMD ["npm", "run", "dev"]
