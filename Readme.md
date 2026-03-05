# 🤖 Resume Telegram Bot

PHP-бот для Telegram на базе [irazasyed/telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk).  
Обычный PHP-проект без фреймворка, запускается в Docker.

---

## 📋 Требования

| Инструмент     | Версия  |
|----------------|---------|
| Docker         | ≥ 24.x  |
| Docker Compose | ≥ 2.x   |
| Make           | любая   |

> PHP и Composer устанавливать локально **не нужно** — всё работает внутри контейнера.

---

## 🚀 Быстрый старт

```bash
# 1. Клонировать
git clone https://github.com/your-org/resume-bot.git
cd resume-bot

# 2. Создать .env и вписать токен
make env
# отредактировать .env: TELEGRAM_BOT_TOKEN=...

# 3. Установить зависимости и запустить
make install
make up
make logs
```

---

## 📁 Структура проекта

```
resume-bot/
├── src/
│   ├── bot.php        # точка входа
│   └── ...            # App\ namespace
├── .env.example
├── .dockerignore
├── composer.json
├── composer.lock
├── docker-compose.yml
├── Dockerfile
└── Makefile
```

---

## ⚙️ Режимы работы

### Long-Polling (по умолчанию)

Бот сам опрашивает Telegram. Подходит для разработки и продакшена без публичного домена.

```bash
make up
make logs
```

### Webhook

Требует публичный HTTPS URL. Токен регистрируется через Telegram API напрямую — обработчик пишется в `src/` самостоятельно под свой веб-сервер.

```bash
# Указать в .env:
# WEBHOOK_URL=https://yourdomain.com/webhook

make set-webhook    # зарегистрировать
make webhook-info   # проверить статус
make delete-webhook # отключить, вернуться к polling
```

---

## 🛠 Команды Make

```bash
make help  # показать все команды
```

| Команда               | Описание                                   |
|-----------------------|--------------------------------------------|
| `make env`            | Создать `.env` из `.env.example`           |
| `make install`        | `composer install` внутри Docker           |
| `make composer-update`| Обновить зависимости                       |
| `make build`          | Пересобрать Docker-образ                   |
| `make up`             | Запустить бот                              |
| `make down`           | Остановить контейнер                       |
| `make restart`        | Перезапустить                              |
| `make logs`           | Логи в реальном времени                    |
| `make shell`          | Войти в контейнер (`sh`)                   |
| `make set-webhook`    | Зарегистрировать webhook в Telegram        |
| `make delete-webhook` | Удалить webhook                            |
| `make webhook-info`   | Статус текущего webhook                    |
| `make lint`           | Проверить синтаксис PHP файлов в `src/`    |
| `make test`           | Запустить тесты (pest / phpunit)           |
| `make prod-build`     | Собрать production-образ                   |
| `make prod-up`        | Запустить в production                     |
| `make clean`          | Удалить локальный `vendor/`                |
| `make prune`          | Удалить контейнеры, volumes, образы        |

---

## 🌍 Переменные окружения

| Переменная              | Обязательная | Описание                          |
|-------------------------|:------------:|-----------------------------------|
| `TELEGRAM_BOT_TOKEN`    | ✅           | Токен от @BotFather               |
| `TELEGRAM_BOT_USERNAME` | ❌           | Username бота (без @)             |
| `APP_ENV`               | ❌           | `development` / `production`      |
| `APP_DEBUG`             | ❌           | `true` / `false`                  |
| `WEBHOOK_URL`           | ❌           | HTTPS URL для webhook             |

---

## 🐳 Docker образы (multi-stage)

| Stage         | Назначение                                      |
|---------------|-------------------------------------------------|
| `base`        | PHP 8.2-alpine + zip, mbstring                  |
| `development` | Все зависимости Composer, volume-маунт для кода |
| `production`  | Только `--no-dev`, запуск от non-root юзера     |

```bash
make prod-build   # собрать production
make prod-up      # запустить production
```

---

## 📦 Основные зависимости

| Пакет                        | Версия  | Описание             |
|------------------------------|---------|----------------------|
| `irazasyed/telegram-bot-sdk` | ^3.15   | Telegram Bot API SDK |
| `guzzlehttp/guzzle`          | ^7.10   | HTTP-клиент          |

---

## 📄 Лицензия

MIT