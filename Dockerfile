FROM php:8.2-cli-alpine

# ランタイム用ライブラリ
RUN apk add --no-cache libpng libjpeg-turbo freetype ffmpeg

# ── GD をビルド ──
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
      libpng-dev libjpeg-turbo-dev freetype-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd pdo_mysql \
  && apk del .build-deps   # ビルド専用パッケージ削除

WORKDIR /workspace
COPY . .  

ENV GOOGLE_AUTH_DISABLE_CREDENTIALS_FILE_SEARCH=true
ENV GOOGLE_AUTH_SUPPRESS_CREDENTIALS_WARNINGS=true

CMD exec php \
    -d max_execution_time=0 \
    -S 0.0.0.0:8080 \
    -t /workspace/public /workspace/router.php
