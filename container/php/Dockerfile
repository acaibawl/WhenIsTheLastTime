FROM php:8.4-fpm

# 作業ディレクトリ設定
WORKDIR /var/www/html

# システムパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能のインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Redis拡張機能のインストール
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ファイルコピーと権限設定
COPY container/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY ./backend /var/www/html
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ポート9000を公開
EXPOSE 9000

CMD ["php-fpm"]
