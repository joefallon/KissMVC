.PHONY: lint deps test ci

# Run quick PHP lint over all PHP files in the repo (suitable for Debian VM).
lint:
	@echo "Running PHP lint on all .php files..."
	@find . -type f -name "*.php" -print0 | xargs -0 -n1 php -l

# Install PHP dependencies via Composer (run from repo root).
deps:
	@echo "Installing composer dependencies..."
	@composer install --no-interaction --prefer-dist

# Run test suite via PHPUnit (prefer vendor binary when available).
test:
	@echo "Running tests..."
	@if [ -x vendor/bin/phpunit ]; then \
		vendor/bin/phpunit --colors=always; \
	else \
		composer exec phpunit -- --colors=always; \
	fi

# CI shortcut: install deps, lint, then run tests.
ci: deps lint test

