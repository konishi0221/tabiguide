# Dockerfile
FROM php:8.1-cli

# 必要な拡張をインストール
RUN docker-php-ext-install pdo pdo_mysql

# 作業ディレクトリを /workspace に
WORKDIR /workspace

# ソースをコピー
COPY . .

CMD exec php \
    -d open_basedir=/workspace:/srv:/tmp \
    -d max_execution_time=0 \
    -S 0.0.0.0:8080 \
    -t public router.php
