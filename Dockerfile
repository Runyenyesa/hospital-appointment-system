FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        git unzip zip libzip-dev libssl-dev ca-certificates && \
    docker-php-ext-install zip pdo pdo_mysql && \
    rm -rf /var/lib/apt/lists/*

COPY . /app

RUN mkdir -p /app/uploads && chmod 777 /app/uploads

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
