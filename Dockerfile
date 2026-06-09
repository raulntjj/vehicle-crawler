###############################################################################
# Vehicle Crawler ETL — Dockerfile
# PHP 8.4 CLI com extensões para Laravel + RabbitMQ + PostgreSQL
###############################################################################

FROM php:8.4-cli-alpine

# ---------------------------------------------------------------------------
# Dependências de sistema
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    libpq-dev \
    linux-headers \
    $PHPIZE_DEPS

# ---------------------------------------------------------------------------
# Extensões PHP necessárias
# ---------------------------------------------------------------------------
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    sockets \
    bcmath \
    pcntl

# ---------------------------------------------------------------------------
# Composer (cópia do binário da imagem oficial)
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Diretório de trabalho
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Em desenvolvimento, os arquivos são montados via volume no docker-compose.
# Em produção, descomente as linhas abaixo:
# ---------------------------------------------------------------------------
# COPY . .
# RUN composer install --no-interaction --no-progress --optimize-autoloader --no-dev
# RUN chmod -R 775 storage bootstrap/cache

# Mantém o container rodando (será sobrescrito no docker-compose)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
