all: up

up:
	./vendor/bin/sail up -d

down:
	./vendor/bin/sail down

migrate:
	./vendor/bin/sail artisan migrate

migrate-rollback:
	./vendor/bin/sail artisan migrate:rollback

migrate-refresh:
	./vendor/bin/sail artisan migrate:refresh

destroy:
	docker compose down --rmi all --volumes --remove-orphans

sh:
	./vendor/bin/sail shell

test:
	./vendor/bin/sail test

