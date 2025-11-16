<?php
declare(strict_types=1);
/**
 * Copyright (c) 2015-2025 Joseph Fallon <joseph.t.fallon@gmail.com>
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
$environment = defined('APPLICATION_ENV')
    ? (string)APPLICATION_ENV
    : (string)(getenv('APPLICATION_ENV') ?: 'development');


// Initialize the configuration array.
$config = [];

// Ensure the environment is always available in the config array.
$config = ['environment'] ?? $environment;

/**
 * Database settings
 *
 * Use environment variables when available. The defaults below are placeholders
 * so newcomers can run the app locally after editing them.
 */
if($environment == 'production')
{
    // Production Database Settings
    $config['db'] = [
        'name' => 'db-name',
        'host' => 'db-host',
        'user' => 'db-username',
        'pass' => 'db-password'
    ];
}
else
{
    // Development Database Settings
    $config['db'] = [
        'name' => 'dev-db-name',
        'host' => 'dev-db-host',
        'user' => 'dev-db-username',
        'pass' => 'dev-db-password'
    ];
}

// Alternatively, use environment variables for all environments. This is often
// preferred in containerized deployments where environment variables are easy
// to manage. Uncomment the block below to use this approach.
//$config['db'] = [
//    'name' => getenv('DB_NAME') ?: 'db-name',
//    'host' => getenv('DB_HOST') ?: 'db-host',
//    'user' => getenv('DB_USER') ?: 'db-username',
//    'pass' => getenv('DB_PASS') ?: 'db-password',
//];

/**
 * Application secret key
 *
 * Used for hashing, tokens, etc. IMPORTANT: Do not commit a production secret
 * to source control. Prefer setting SECRET_KEY as an environment variable in
 * production.
 *
 * The default below is a placeholder so newcomers can run the app locally after
 * editing it. In production, set the SECRET_KEY environment variable to a
 * strong, random value.
 */
$config['secret_key'] = getenv('SECRET_KEY') ?: 'place-your-super-secret-key-here';

/**
 * SSL requirement
 *
 * Use an environment variable to toggle SSL requirement in deployment
 * without editing source code. Default to false for local development.
 */
if($environment === 'production')
{
    // In production, prefer the environment variable but default to true.
    $config['ssl_required'] = filter_var(getenv('SSL_REQUIRED') ?: 'true', FILTER_VALIDATE_BOOLEAN);
}
else
{
    // In non-production environments, default to false.
    $config['ssl_required'] = filter_var(getenv('SSL_REQUIRED') ?: 'false', FILTER_VALIDATE_BOOLEAN);
}

/**
 * Timezone setting
 *
 * Use an environment variable to set the timezone in deployment without
 * editing source code. Default to 'UTC' which is a sensible default for most
 * applications.
 */
$config['timezone'] = getenv('APP_TIMEZONE') ?: 'UTC';

/**
 * Views and templates
 *
 * Set paths to views, partials, and layouts directories. These are relative to
 * BASE_PATH. Adjust as needed if your project structure differs.
 */
$config['views_directory']    = BASE_PATH . '/Application/Views';
$config['partials_directory'] = BASE_PATH . '/Application/Partials';
$config['layouts_directory']  = BASE_PATH . '/Application/Layouts';


// Cleanup temporaries.
unset($environment);

// Return the configuration array. Application::loadConfiguration supports this
// form and will merge it into the application's registry.
return $config;
