DC=docker compose

.PHONY: setup up down restart logs shell composer-install npm-install migrate fixtures test-backend test-frontend test-e2e build-backend build-frontend

setup: build-backend npm-install up composer-install migrate

build-backend:
	$(DC) build backend

up:
	$(DC) up -d

down:
	$(DC) down

restart: down up

logs:
	$(DC) logs -f

shell:
	$(DC) exec backend bash

composer-install:
	$(DC) exec backend composer install

npm-install:
	$(DC) run --rm --no-deps frontend npm install

migrate:
	$(DC) exec backend php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	$(DC) exec backend php bin/console doctrine:fixtures:load --no-interaction

test-backend:
	$(DC) exec backend php bin/phpunit

test-frontend:
	$(DC) exec frontend npm test -- --watch=false

test-e2e:
	$(DC) exec frontend npm run e2e

build-frontend:
	$(DC) exec frontend npm run build
