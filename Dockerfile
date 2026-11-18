# Dockerfile — TP 6, corrigé. Le code est DANS l'image, et le processus ne tourne pas en root.
FROM php:8.4-cli
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --chown=www-data:www-data . /app
USER www-data
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
