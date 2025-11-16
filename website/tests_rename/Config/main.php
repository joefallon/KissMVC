<?php declare(strict_types=1);
/**
 * Copyright (c) 2015-2025 Joseph Fallon <joseph.t.fallon@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 * tests/config/main.php
 * ---------------------
 * Purpose
 * -------
 * This file bootstraps configuration that the test-suite expects to be present
 * when it runs. It is intentionally minimal and test-focused: it configures
 * error reporting/timezone, resolves project paths (BASE_PATH, APP_PATH,
 * TESTS_PATH), provides database settings via environment variables with
 * sensible defaults, and requires Composer's autoloader.
 *
 * Why this exists for tests
 * -------------------------
 * Tests often run in a different context than the web front-controller
 * (different working dir, no web server environment variables, etc.). This
 * file makes sure basic constants and autoloading are available so tests can
 * instantiate app classes and run deterministically.
 *
 * PHP version
 * -----------
 * - Written for PHP 7.4+ (typed closures, declare(strict_types=1)). If you
 *   upgrade PHP in the future, ensure backward compatibility of any added
 *   syntax.
 *
 * What this file sets up (high level)
 * -----------------------------------
 * - Error reporting and display for easier test debugging
 * - Timezone fallback when none is configured
 * - BASE_PATH resolved from this file's location (two levels up)
 * - APP_PATH and TESTS_PATH derived from BASE_PATH
 * - DB_* constants loaded from environment variables (with safe defaults)
 * - Inclusion of Composer autoloader (vendor/autoload.php)
 *
 * Environment variables / configuration
 * -------------------------------------
 * The file reads the following environment variables and defines corresponding
 * constants (only if they are not already defined):
 *
 *   - DB_NAME (default: "dbname")
 *   - DB_HOST (default: "localhost")
 *   - DB_USER (default: "username")
 *   - DB_PASS (default: "password")
 *
 * These are intentionally permissive defaults so tests can run out-of-the-box
 * in local environments. For CI or production-like tests, override them using
 * environment variables.
 *
 * Setting environment variables (examples)
 * ----------------------------------------
 * - Windows (temporary for current cmd session):
 *     set DB_NAME=mytests
 *     set DB_USER=testuser
 *     php vendor/bin/phpunit
 *
 * - Windows (persistent - requires reopening shell):
 *     setx DB_NAME mytests
 *
 * - UNIX-like (bash):
 *     export DB_NAME=mytests
 *     ./vendor/bin/phpunit
 *
 * - Preferable: use a dotenv library (vlucas/phpdotenv) or CI secrets to
 *   inject configuration instead of committing secrets to the repository.
 *
 * Security note
 * -------------
 * Avoid committing real credentials or secrets into this repo. Do not replace
 * the fallback DB_PASS with a real password in version control. Use CI
 * secrets, environment variables, or a secure .env management strategy.
 *
 * Path resolution details
 * -----------------------
 * The file resolves BASE_PATH with:
 *   realpath(__DIR__ . '/../../')
 * which assumes this file is located at: <BASE_PATH>/tests/config/main.php
 * If you move the tests directory, update this expression accordingly. The
 * derived constants APP_PATH and TESTS_PATH are based on BASE_PATH and are
 * intended to provide a stable location for requiring app files.
 *
 * Composer autoloader
 * -------------------
 * The file expects Composer's autoloader at: BASE_PATH . '/vendor/autoload.php'
 * If you see the RuntimeException complaining that the autoloader is missing,
 * run:
 *   composer install
 * from the project root (BASE_PATH). If your vendor directory is located
 * elsewhere, update the $autoload path below.
 *
 * Troubleshooting / quick checklist when you return in 6 months
 * -------------------------------------------------------------
 * - Confirm PHP version (7.4+) and update syntax if the project moves to a
 *   newer minimum PHP.
 * - If tests fail with class-not-found errors, ensure `composer install` has
 *   been run and `vendor/autoload.php` exists at the expected path.
 * - To change database settings for CI or local runs, set DB_NAME/DB_USER/etc
 *   in your CI config or use a dotenv file and load it prior to this file.
 * - If timezone-related failures appear, set the desired timezone in php.ini
 *   or set the environment variable `TZ`, or change the fallback below.
 * - If you refactor the repo layout (move `tests`), update the BASE_PATH
 *   resolution expression.
 *
 * Extensibility notes
 * -------------------
 * - If you want to load more test-specific config (e.g. test cache dirs,
 *   logging settings), extend this file or require an additional
 *   `tests/config/local.php` that is gitignored.
 * - Consider switching to a 12-factor `env` approach if you add more
 *   configurable values. A small helper (or dependency on vlucas/phpdotenv)
 *   can centralize environment loading.
 *
 * History / Author
 * ----------------
 * - Modernized for PHP 7.4 to enable strict types, better error reporting,
 *   and clearer path/env handling.
 *
 * Quick return checklist (short):
 * 1) Check PHP version
 * 2) composer install
 * 3) Verify DB_ env vars (or CI secrets)
 * 4) Run tests (vendor/bin/phpunit or tests/index.php depending on suite)
 */

// Enable strict error reporting for tests.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Only set timezone when none is configured.
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Resolve and validate base path.
$basePath = realpath(__DIR__ . '/../../');
if ($basePath === false) {
    throw new RuntimeException('Could not resolve BASE_PATH from `' . __FILE__ . '`');
}
$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath);
}

if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/Application');
}

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', BASE_PATH . '/Tests');
}

// Helper to get environment values with sensible fallbacks.
$getEnv = static function (string $name, string $fallback): string {
    $val = getenv($name);
    if ($val === false) {
        $val = $_ENV[$name] ?? $_SERVER[$name] ?? $fallback;
    }
    return (string) $val;
};

if (!defined('DB_NAME')) {
    define('DB_NAME', $getEnv('DB_NAME', 'dbname'));
}
if (!defined('DB_HOST')) {
    define('DB_HOST', $getEnv('DB_HOST', 'localhost'));
}
if (!defined('DB_USER')) {
    define('DB_USER', $getEnv('DB_USER', 'username'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $getEnv('DB_PASS', 'password'));
}

// Require Composer autoloader and fail fast if missing.
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException('Composer autoload not found at: ' . $autoload . '. Run `composer install`.');
}
require_once $autoload;
