#!/bin/bash

# 秘密鍵と、対になるサーバー証明書をまとめて作成
openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout container/nginx/certs/when_is_the_last_time.local.key \
  -out container/nginx/certs/when_is_the_last_time.local.crt \
  -config container/nginx/certs/openssl-san.cnf \
  -extensions req_ext
