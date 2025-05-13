FROM php:8.2-fpm

# タイムゾーン設定
ENV TZ=Asia/Tokyo

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip \
    bcmath \
    opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Composerのインストール
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# 作業ディレクトリを設定
WORKDIR /var/www

# Laravel最適化のためのPHP設定
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time = 120" > /usr/local/etc/php/conf.d/max-execution-time.ini

# Laravel用ディレクトリのパーミッション設定
RUN chmod 777 -R /var/www 