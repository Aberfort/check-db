up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f --tail=200

sh:
	docker compose exec app sh

node:
	docker compose exec node sh

composer:
	docker compose exec app composer install

key:
	docker compose exec app php artisan key:generate

migrate:
	docker compose exec app php artisan migrate

queue-table:
	docker compose exec app php artisan queue:table

npm-build:
	docker compose exec node npm run build
