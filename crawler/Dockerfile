FROM php:8.4-cli-alpine

# ---------------------------------------------------------------------------
# Dependências de sistema básicas
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    supervisor \
    postgresql-dev \
    rabbitmq-c-dev \
    autoconf \
    build-base
        
# ---------------------------------------------------------------------------
# Extensões PHP
# ---------------------------------------------------------------------------
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo \
    pdo_pgsql \
    pgsql \
    sockets \
    bcmath \
    pcntl \
    mongodb \
    amqp \
    redis

RUN apk del autoconf build-base

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
RUN mkdir -p /var/log/supervisor /etc/supervisor/conf.d
COPY supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor/worker.conf /etc/supervisor/conf.d/worker.conf
COPY supervisor/scheduler.conf /etc/supervisor/conf.d/scheduler.conf

# ---------------------------------------------------------------------------
# Diretório de trabalho
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# Garantir que a estrutura de pastas do Laravel e dos workers exista com as permissões corretas
RUN mkdir -p storage/logs/workers \
             storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]