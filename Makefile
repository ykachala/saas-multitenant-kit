.PHONY: up down shell test migrate seed horizon pint stan

up:
	docker compose up -d

down:
	docker compose down

shell:
	docker compose exec app sh

test:
	docker compose exec app ./vendor/bin/pest

test-coverage:
	docker compose exec app ./vendor/bin/pest --coverage

migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

horizon:
	docker compose exec app php artisan horizon

tinker:
	docker compose exec app php artisan tinker

pint:
	docker compose exec app ./vendor/bin/pint

stan:
	docker compose exec app ./vendor/bin/phpstan analyse

key:
	docker compose exec app php artisan key:generate

cache-clear:
	docker compose exec app php artisan cache:clear

routes:
	docker compose exec app php artisan route:list
