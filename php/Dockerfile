FROM php:8.2-fpm

# Switch apt sources to Tsinghua mirror (mainland China)
RUN sed -i 's@deb.debian.org@mirrors.tuna.tsinghua.edu.cn@g' /etc/apt/sources.list.d/debian.sources

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo_mysql \
        zip \
        exif \
        intl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Use Aliyun mirror for Composer (mainland China)
RUN composer config -g repos.packagist composer https://mirrors.aliyun.com/composer/

# Xdebug 3 —— 供 VSCode 断点调试使用（触发式，不影响正常访问性能）
RUN pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug \
    && printf '%s\n' \
        "zend_extension=xdebug" \
        "xdebug.mode=debug" \
        "xdebug.start_with_request=trigger" \
        "xdebug.client_host=host.docker.internal" \
        "xdebug.client_port=9003" \
        "xdebug.idekey=VSCODE" \
        "xdebug.discover_client_host=1" \
        "xdebug.log_level=0" \
        > /usr/local/etc/php/conf.d/zz-xdebug.ini

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/zz-flarum.ini \
    && echo "upload_max_filesize=32M" >> /usr/local/etc/php/conf.d/zz-flarum.ini \
    && echo "post_max_size=32M" >> /usr/local/etc/php/conf.d/zz-flarum.ini \
    && echo "max_execution_time=120" >> /usr/local/etc/php/conf.d/zz-flarum.ini

WORKDIR /var/www/flarum
