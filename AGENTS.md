AGENTS.md
=========

Purpose
-------
This document explains how to run PHP tooling (lint, composer, PHPUnit)
against this repository when PHP is not installed locally but you have a
Debian 12 virtual machine (VM) that your editor (PHPStorm) syncs to.

Goals
-----
- Provide copy-paste commands to lint and test the project on the VM.
- Record recommended SSH options and troubleshooting tips.
- Keep instructions simple (KISS) so you can return in months and quickly
  get the environment working.

Before you begin
----------------
1. Ensure your Debian 12 VM is reachable via SSH from your host machine.
   Test from your host with:

   ssh user@vm-host.example.com

   Replace `user` and `vm-host.example.com` with your username and host or
   IP address.

2. Ensure the project folder is available on the VM. Two common setups:
   - PHPStorm remote sync: your editor already mirrors the repository to the
     VM path (preferred for development).
   - Shared folder / NFS: the project directory is accessible from the VM.

3. On the VM, install required packages once (run as user with sudo):

   sudo apt update && sudo apt install -y php-cli php-xml php-mbstring \
       php-intl php-curl unzip git

   Then install Composer (globally) if not already installed:

   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
     && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
     && php -r "unlink('composer-setup.php');"

Commands to run on the VM (copy-paste)
-------------------------------------
Open an SSH session and run these from the repository root on the VM.
If PHPStorm syncs to a different path on the VM, `cd` there first.

# Change to the project directory (adjust path as needed)
cd /path/to/your/project/website

# 1) Validate PHP file syntax (quick lint)
php -l lib/KissMVC/Application.php
php -l lib/KissMVC/Controller.php
php -l lib/KissMVC/ControllerFactoryInterface.php
# ...repeat for any file you changed or use a small loop:
for f in $(find lib -name "*.php"); do php -l "$f"; done

# 2) Install PHP dependencies (composer)
composer install --no-interaction --prefer-dist

# 3) Run tests (if project uses phpunit via composer)
# If vendor/bin/phpunit is present:
vendor/bin/phpunit --colors=always
# Alternatively, use composer exec if bin is not executable:
composer exec phpunit -- --colors=always

# 4) Optional: run a small code style or static analysis (if you have tools)
# Example: phpstan or phpcs if configured in the project
# composer require --dev phpstan/phpstan
# vendor/bin/phpstan analyse src tests

# Running the provided CI script
# ------------------------------
# The project includes scripts/ci-run.sh which:
#  - automatically cds to the repository root (so you can run it from
#    the scripts/ directory),
#  - sets COMPOSER_ALLOW_SUPERUSER=1 when run as root to avoid Composer
#    failing in root shells (it warns but proceeds), and
#  - sets XDEBUG_MODE=off by default so Xdebug's step debug messages do not
#    clutter CI output.
# To run the script from anywhere inside the repo:
./scripts/ci-run.sh

Troubleshooting notes about the earlier error you saw
-----------------------------------------------------
The error you pasted:

  Composer could not find a composer.json file in /var/www/kissmvc/scripts

happened because the script was executed while the current working directory
was `scripts/` instead of the repository root. Composer looks for
`composer.json` in the cwd, so it failed. The updated `scripts/ci-run.sh`
resolves that by `cd`-ing to the repo root before running `composer`.

You may also have seen:

  Xdebug: [Step Debug] Could not connect to debugging client...

This comes from Xdebug trying to connect to an IDE during script runs. The
CI script disables Xdebug step debug for its run (via `XDEBUG_MODE=off`) to
avoid noisy messages. If you prefer to keep Xdebug on, set `XDEBUG_MODE` in
your environment before running the script.

Notes on paths and PHPStorm remote sync
--------------------------------------
- PHPStorm can be configured with a Remote Interpreter and automatic
  deployment. If configured, edits you make locally will be synchronized to
  the VM automatically and tests run on the VM will use those files.
- Common PHPStorm options to check:
  - Deployment > Mappings: make sure local project folder maps to VM folder.
  - PHP > CLI Interpreter: set the remote interpreter pointing to the VM.

Coding guidelines: Clean Code & KISS
-----------------------------------
This repository follows Robert C. Martin's Clean Code principles and the KISS
(Keep It Simple Stupid) philosophy. These aren't just suggestions — they are
practical guardrails to keep the codebase readable, maintainable, and easy to
test. Below are concrete rules and a short PR checklist to make these ideas
actionable for contributors.

Core principles
---------------
- Small functions: each function should do one thing and do it well. Prefer
  short, descriptive names over long comments.
- Meaningful names: use clear names for variables, functions, and classes.
  Names are the first form of documentation.
- Single Responsibility: classes and modules should have one reason to change.
- Avoid premature abstraction: keep things simple, refactor when there is a
  second client or clear repetition.
- Prefer composition over inheritance when it improves clarity and testability.
- Fail fast and loudly: validate inputs and throw clear exceptions rather
  than returning ambiguous values.
- Tests: add unit tests for non-trivial logic and for regression-prone areas.
- Comment sparingly: prefer clear code to comments. Use comments for why,
  not what. Keep comments short and relevant.

PR checklist (before merging)
----------------------------
- Does the code have a clear and small scope? (One feature / one bug fix.)
- Are function and variable names descriptive and unambiguous?
- Are functions short and focused (ideally under ~50 lines)?
- Are public behaviors covered by tests? Add a test when fixing a bug.
- Does the change avoid global side-effects and hidden state? Prefer
  dependency injection or explicit registry usage.
- Is documentation updated when public APIs or expected config change?
- Does linting pass locally (`make lint`) and on CI (`make ci`)?
- Are lines wrapped at or under ~100 characters for readability?

How to enforce on the VM
------------------------
- Use the included `Makefile` and `scripts/ci-run.sh` to lint and run tests.
- Consider adding pre-commit hooks (husky-like workflows for PHP) to run
  `make lint` before committing.
- Use CI (GitHub Actions / GitLab CI) to run `make ci` on every PR.

Troubleshooting and common pitfalls
----------------------------------
- If you encounter failures that look environment-specific, run `php -v` and
  `composer diagnose` on the VM to inspect PHP and Composer health.
- If Composer requires extensions (ext-*) install the missing PHP ext packages
  on the VM (for Debian: `sudo apt install php-<extname>`).
- If tests fail due to missing config, check `tests/config/main.php` for
  environment variable expectations and set them in the VM shell or CI.

Quick checklist when returning later
-----------------------------------
- Confirm VM reachable: `ssh user@vm`.
- Confirm sync path and `cd` into project root on the VM.
- Run `php -v` to check PHP version (Debian 12 generally ships PHP 8.1+).
- Run `composer install` then `make lint` and `make test` or `scripts/ci-run.sh`.

If you want me to add automation
--------------------------------
I can add the following to the repository if you want:
- A pre-commit hook to run `make lint` automatically.
- GitHub Actions to run `make ci` on PRs.
- phpstan/phpcs configuration in composer.json and a basic ruleset.

Tell me which of the above you'd like next and I will add it.
