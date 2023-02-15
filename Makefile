PHP 			= php
COMPOSER        = composer
M               = $(shell printf "\033[34;1m>>\033[0m")

.PHONY: test
test:
	$(info $(M) runing tests...)
	$(COMPOSER) tests

.PHONY: lint
lint:
	$(info $(M) runing cs fixer...)
	$(COMPOSER) cs