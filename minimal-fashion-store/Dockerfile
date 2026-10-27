# syntax=docker/dockerfile:1
FROM php:8.4-cli-bookworm
RUN --mount=type=secret,id=proxy_ca,target=/run/secrets/proxy_ca \
    if [ -f /run/secrets/proxy_ca ]; then printf 'Acquire::https::CaInfo "/run/secrets/proxy_ca";\n' > /etc/apt/apt.conf.d/99proxy-ca; fi; \
    apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev libxml2-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring intl zip pcntl bcmath \
    && rm -rf /var/lib/apt/lists/* /etc/apt/apt.conf.d/99proxy-ca
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
