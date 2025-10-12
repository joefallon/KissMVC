<?php
declare(strict_types=1);
/**
 * Copyright (c) 2025 Joseph Fallon <joseph.t.fallon@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
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
 * Application configuration (main)
 *
 * Modernized for PHP 7.4 and Clean Code principles:
 *  - Returns a configuration array (preferred by Application::loadConfiguration)
 *  - Uses environment variables where appropriate so secrets and per-host
 *    overrides do not live in source control
 *  - Provides clear, friendly documentation and examples so contributors
 *    can extend this file safely (KISS + readability)
 *
 * Notes for contributors:
 *  - Application::loadConfiguration accepts a file that either returns an
 *    array or assigns $config. Returning an array is the modern preference.
 *  - Prefer environment variables for secrets and deployment-specific values.
 *  - Keep values simple; add explanatory comments for non-obvious settings.
 */

// Determine the application environment in a predictable order:
// 1) constant APPLICATION_ENV if defined (legacy bootstraps)
// 2) environment variable APPLICATION_ENV
// 3) default to 'development'
$env = defined('APPLICATION_ENV')
    ? (string) APPLICATION_ENV
    : (string) (getenv('APPLICATION_ENV') ?: 'development');

// Determine BASE_PATH: prefer the existing constant, otherwise infer a sane
// default (repository/website directory). This allows the config to be used
// in a variety of bootstraps without hard failures.
$defaultBasePath = dirname(__DIR__, 2); // <repo>/website
$basePath = defined('BASE_PATH') ? (string) BASE_PATH : $defaultBasePath;
$basePath = rtrim($basePath, "\/\\");

// Helper to resolve project relative paths. Accept an array of parts to make
// the callable signature explicit for static analyzers and for future reuse.
$join = static function (array $parts) use ($basePath): string {
    return $basePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
};

// -----------------------------------------------------------------------------
// Database settings
// -----------------------------------------------------------------------------
// Use environment variables when available. The defaults below are placeholders
// so newcomers can run the app locally after editing them.
$db = [
    'name' => getenv('DB_NAME') ?: 'db-name',
    'host' => getenv('DB_HOST') ?: 'db-host',
    'user' => getenv('DB_USER') ?: 'db-username',
    'pass' => getenv('DB_PASS') ?: 'db-password',
];

// -----------------------------------------------------------------------------
// Core configuration array
// -----------------------------------------------------------------------------
$config = [
    // Environment tag (development, production, testing, etc.)
    'environment' => $env,

    // Database connection details. Replace with environment-specific values.
    'db' => $db,

    // Secret key used for application hashes, tokens, etc.
    // IMPORTANT: Do not commit a production secret to source control. Prefer
    // setting SECRET_KEY as an environment variable in production.
    'secret_key' => getenv('SECRET_KEY') ?: 'place-your-super-secret-key-here',

    // Force SSL for the entire site when true. Use an environment variable to
    // toggle this in deployment without editing source code.
    'ssl_required' => filter_var(getenv('SSL_REQUIRED') ?: 'false', FILTER_VALIDATE_BOOLEAN),

    // Timezone used for storing/formatting times. Default to UTC.
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',

    // Directories used by the view/layout/partial system. Resolved from
    // BASE_PATH for portability. These can be overridden in deployments via
    // a runtime registry or by editing this file.
    'views_directory' => $join(['application', 'views']),
    'partials_directory' => $join(['application', 'partials']),
    'layouts_directory' => $join(['application', 'layouts']),

    // Place additional application configuration keys below. Keep entries
    // small, well-named, and documented.
];

// -----------------------------------------------------------------------------
// Environment-specific overrides
// -----------------------------------------------------------------------------
// Allow a simple pattern for env-specific values. Keep overrides explicit and
// easy to find.
if ($env === 'production') {
    // Example: in production you may wish to tighten defaults. Avoid putting
    // secrets here; prefer environment variables.

    // Intentionally present so static analysis tools do not report an empty
    // block. Apply production overrides here when needed.
    $config['__production_placeholder'] = $config['__production_placeholder'] ?? true;
}

// Return the configuration array. Application::loadConfiguration supports this
// form and will merge it into the application's registry.
return $config;
