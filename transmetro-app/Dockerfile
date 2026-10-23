# Transmetro — imagen de producción: PHP 8.4 + Apache + pdo_pgsql (PostgreSQL) y, opcional, oci8 (Oracle)
# Funciona en x86_64 y en ARM64 (Ampere A1 de Oracle Cloud Always Free).
FROM php:8.4-apache

ARG TARGETARCH
# WITH_ORACLE=true instala Instant Client + oci8 (Oracle Cloud); en Render basta PostgreSQL.
ARG WITH_ORACLE=false
ENV ORACLE_HOME=/opt/oracle/instantclient \
    LD_LIBRARY_PATH=/opt/oracle/instantclient \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# --- Oracle Instant Client (basic + sdk) desde download.oracle.com ---
RUN apt-get update && apt-get install -y --no-install-recommends unzip curl libaio1t64 libzip-dev libpq-dev libpng-dev \
    && rm -rf /var/lib/apt/lists/*
RUN [ "$WITH_ORACLE" = "true" ] || exit 0 \
    && ln -sf /usr/lib/*-linux-gnu/libaio.so.1t64 /usr/lib/libaio.so.1 \
    && if [ "$TARGETARCH" = "arm64" ]; then IC=linux-arm64; else IC=linuxx64; fi \
    && mkdir -p /opt/oracle && cd /opt/oracle \
    && curl -fsSLo basic.zip "https://download.oracle.com/otn_software/linux/instantclient/instantclient-basic-${IC}.zip" \
    && curl -fsSLo sdk.zip   "https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-${IC}.zip" \
    && unzip -q basic.zip && unzip -q -o sdk.zip && rm basic.zip sdk.zip \
    && mv instantclient_* instantclient \
    && echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle.conf && ldconfig

# --- Extensiones PHP ---
RUN if [ "$WITH_ORACLE" = "true" ]; then \
        echo "instantclient,/opt/oracle/instantclient" | pecl install oci8 && docker-php-ext-enable oci8; \
    fi \
    && docker-php-ext-install zip opcache pdo_pgsql gd

# --- Apache: servir /public y habilitar rewrite ---
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-req=ext-oci8

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && rm -f .env bootstrap/cache/*.php \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
