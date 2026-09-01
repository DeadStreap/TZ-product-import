.PHONY: up down build migrate seed test lint fix frontend-install frontend-dev frontend-build setup logs restart

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

logs:
	docker compose logs -f app

restart:
	docker compose restart app messenger

migrate:
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

seed:
	docker compose exec app php bin/console db:fixtures:load

test:
	docker compose exec app php vendor/bin/phpunit

lint:
	docker compose exec app vendor/bin/phpstan analyse
	docker compose exec app vendor/bin/php-cs-fixer fix --dry-run

fix:
	docker compose exec app vendor/bin/php-cs-fixer fix

frontend-install:
	cd frontend && npm install

frontend-dev:
	cd frontend && npx ng serve

frontend-build:
	cd frontend && npx ng build --configuration production

setup: build up migrate
	@echo "Setup complete! Run 'make frontend-dev' in another terminal."
