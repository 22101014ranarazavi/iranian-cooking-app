FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]
