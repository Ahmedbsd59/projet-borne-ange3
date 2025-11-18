.PHONY: test install

install:
	composer install --no-interaction

test:
	vendor/bin/phpunit --testdox

test-coverage:
	vendor/bin/phpunit --coverage-text
