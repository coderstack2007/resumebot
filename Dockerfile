FROM php:8.4-cli-alpine AS base

LABEL maintainer="resume-bot"
LABEL description="Resume Telegram Bot"

RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        zip \
        mbstring \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ─── deps stage ───────────────────────────────────────────────────────────────
FROM base AS deps

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist

# ─── development stage ────────────────────────────────────────────────────────
FROM base AS development



COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist

COPY . .

CMD ["php", "src/bot.php"]

# ─── production stage ─────────────────────────────────────────────────────────
FROM base AS production

ENV APP_ENV=production

COPY --from=deps /app/vendor ./vendor
COPY . .

RUN addgroup -S botgroup && adduser -S botuser -G botgroup
USER botuser

CMD ["php", "src/bot.php"]