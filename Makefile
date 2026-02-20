PHP             = php
COMPOSER        = composer
DOCKER_IMAGE    = php-apk-parser-test
DOCKER_RUN      = docker run --rm -v "$(CURDIR):/app" -w /app $(DOCKER_IMAGE)
M               = $(shell printf "\033[34;1m>>\033[0m")

.PHONY: docker-build
docker-build:
	$(info $(M) building docker php image...)
	docker build -t $(DOCKER_IMAGE) -f docker/php/Dockerfile .

.PHONY: docker-install
docker-install:
	$(info $(M) installing dependencies in docker...)
	$(DOCKER_RUN) composer install --no-interaction

.PHONY: docker-test
docker-test:
	$(info $(M) running tests in docker...)
	$(DOCKER_RUN) composer tests

.PHONY: docker-lint
docker-lint:
	$(info $(M) running cs check in docker...)
	$(DOCKER_RUN) composer cs:check

.PHONY: docker-format
docker-format:
	$(info $(M) running cs fixer in docker...)
	$(DOCKER_RUN) composer cs

.PHONY: docker-static
docker-static:
	$(info $(M) running php lint/static checks in docker...)
	$(DOCKER_RUN) composer lint:php

.PHONY: docker-check
docker-check:
	$(info $(M) running full docker verification...)
	$(MAKE) docker-static
	$(MAKE) docker-test

.PHONY: test
test:
	$(MAKE) docker-test

.PHONY: lint
lint:
	$(MAKE) docker-lint
