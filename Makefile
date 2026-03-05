.PHONY: help build up down restart logs shell \
        install composer-update \
        lint test \
        prod-build prod-up \
        set-webhook delete-webhook webhook-info \
        clean prune

# ─── Colors ────────────────────────────────────────────────────────────────────
GREEN  := \033[0;32m
YELLOW := \033[1;33m
CYAN   := \033[0;36m
RESET  := \033[0m

# ─── Variables ─────────────────────────────────────────────────────────────────
COMPOSE   := docker compose
SERVICE   := bot
CONTAINER := resume_bot

##@ Help
help: ## Show this help message
	@echo ""
	@echo "  $(CYAN)Resume Telegram Bot$(RESET) — Available commands:"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(GREEN)%-22s$(RESET) %s\n", $$1, $$2 } /^##@/ { printf "\n  $(YELLOW)%s$(RESET)\n", substr($$0, 5) }' $(MAKEFILE_LIST)
	@echo ""


install: env ## Install dependencies via Composer (inside Docker)
	$(COMPOSE) run --rm $(SERVICE) composer install

composer-update: ## Update Composer dependencies
	$(COMPOSE) run --rm $(SERVICE) composer update

##@ Docker
build: ## Build Docker image
	$(COMPOSE) build --no-cache

up: ## Start bot (long-polling, detached)
	$(COMPOSE) up -d $(SERVICE)

down: ## Stop and remove containers
	$(COMPOSE) down

restart: ## Restart bot container
	$(COMPOSE) restart $(SERVICE)

logs: ## Follow bot logs
	$(COMPOSE) logs -f $(SERVICE)

shell: ## Open shell inside container
	docker exec -it $(CONTAINER) sh

##@ Telegram Webhook
set-webhook: ## Register webhook URL (reads WEBHOOK_URL from .env)
	@source .env && $(COMPOSE) run --rm $(SERVICE) php -r "\
		require 'vendor/autoload.php'; \
		\$$t = new Telegram\Bot\Api(getenv('TELEGRAM_BOT_TOKEN')); \
		\$$r = \$$t->setWebhook(['url' => getenv('WEBHOOK_URL')]); \
		echo \$$r ? '$(GREEN)Webhook set$(RESET)' : '$(YELLOW)Failed$(RESET)'; echo PHP_EOL;"

delete-webhook: ## Remove webhook (back to long-polling)
	@source .env && $(COMPOSE) run --rm $(SERVICE) php -r "\
		require 'vendor/autoload.php'; \
		\$$t = new Telegram\Bot\Api(getenv('TELEGRAM_BOT_TOKEN')); \
		\$$r = \$$t->deleteWebhook(); \
		echo \$$r ? '$(GREEN)Webhook deleted$(RESET)' : '$(YELLOW)Failed$(RESET)'; echo PHP_EOL;"

webhook-info: ## Get current webhook info from Telegram
	@source .env && $(COMPOSE) run --rm $(SERVICE) php -r "\
		require 'vendor/autoload.php'; \
		\$$t = new Telegram\Bot\Api(getenv('TELEGRAM_BOT_TOKEN')); \
		print_r(\$$t->getWebhookInfo()->toArray());"

##@ Production
prod-build: ## Build production image
	APP_ENV=production $(COMPOSE) build --no-cache

prod-up: ## Start in production mode
	APP_ENV=production $(COMPOSE) up -d $(SERVICE)

##@ Code Quality
lint: ## Check PHP syntax across src/
	@$(COMPOSE) run --rm $(SERVICE) sh -c \
		'find src -name "*.php" | xargs -n1 php -l | grep -v "No syntax errors" || echo "$(GREEN)All files OK$(RESET)"'

test: ## Run tests
	@$(COMPOSE) run --rm $(SERVICE) sh -c \
		'[ -f vendor/bin/pest ] && vendor/bin/pest \
		|| [ -f vendor/bin/phpunit ] && vendor/bin/phpunit \
		|| echo "$(YELLOW)No test runner found$(RESET)"'

##@ Cleanup
clean: ## Remove local vendor directory
	rm -rf vendor

prune: ## Remove containers, volumes and local images
	$(COMPOSE) down -v --rmi local
	docker system prune -f