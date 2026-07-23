.PHONY: build test check

build:
	docker compose build

test:
	docker compose run --rm app composer test

check:
	docker compose run --rm app composer check
