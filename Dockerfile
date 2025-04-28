FROM php:8.2-cli-alpine

# ランタイム用ライブラリ
RUN apk add --no-cache libpng libjpeg-turbo freetype

# ── GD をビルド ──
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
      libpng-dev libjpeg-turbo-dev freetype-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd pdo_mysql \
  && apk del .build-deps   # ビルド専用パッケージ削除

WORKDIR /workspace
COPY . .

# ← この行だけ必ず置き換え！
CMD exec php \
    -d open_basedir=/workspace:/srv:/tmp \
    -d max_execution_time=0 \
    -S 0.0.0.0:8080 \
    -t /workspace/public /workspace/router.php
