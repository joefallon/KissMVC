<?php declare(strict_types=1);
/**
 * Copyright (c) 2025 Joseph Fallon <joseph.t.fallon@gmail.com>
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
 */

namespace KissMVC;

// NOTE: This class relies on Composer's autoloader (PSR-4). The project's
// bootstrap (for example tests/config/main.php or public/index.php) should
// require vendor/autoload.php so classes under the KissMVC\ namespace are
// autoloaded. Manual require_once calls were removed to follow modern PHP
// practices and PSR-4 autoloading.

use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * Lightweight application bootstrapper for KissMVC.
 *
 * Responsibilities:
 *  - Load application/test configuration files.
 *  - Provide a small static registry for config and objects.
 *  - Perform environment checks (SSL, timezone).
 *  - Dispatch the front controller to route requests.
 */
class Application
{
    /**
     * Registry: application configuration and shared objects.
     */
    protected static ?array $config = null;

    /**
     * Load a PHP configuration file.
     *
     * The file can either return an array or assign an array to $config.
     * New values are merged into the existing registry so later loads override
     * earlier keys.
     *
     * @param string $configFilePath Absolute or relative path to a PHP file.
     * @throws RuntimeException if the file does not provide an array config.
     */
    public static function loadConfiguration(string $configFilePath): void
    {
        // Avoid leaking $config from previous includes.
        unset($config);

        // The included file may return an array or set $config.
        $returned = require $configFilePath;

        if (is_array($returned)) {
            $newConfig = $returned;
        } elseif (isset($config) && is_array($config)) {
            $newConfig = $config;
        } else {
            $msg = 'Configuration file "%s" must return an array or assign $config';
            throw new RuntimeException(sprintf($msg, $configFilePath));
        }

        if (self::$config !== null) {
            self::$config = array_merge(self::$config, $newConfig);
        } else {
            self::$config = $newConfig;
        }

        // Cleanup temporaries.
        unset($returned, $newConfig, $config);
    }

    /**
     * Get a value from the registry. Returns null when the key is missing.
     *
     * @param string $registryItemName
     * @return mixed|null
     */
    public static function getRegistryItem(string $registryItemName)
    {
        return self::$config[$registryItemName] ?? null;
    }

    /**
     * Store a value in the registry under the given name.
     *
     * @param string $registryItemName
     * @param mixed $registryItem
     */
    public static function setRegistryItem(string $registryItemName, $registryItem): void
    {
        if (self::$config === null) {
            self::$config = [];
        }

        self::$config[$registryItemName] = $registryItem;
    }

    /**
     * Run app: perform checks and dispatch the front controller.
     *
     * This method now performs a sanity check to ensure the FrontController
     * class is autoloadable. If not, it throws a helpful RuntimeException
     * explaining that vendor/autoload.php must be required by the bootstrap.
     */
    public static function run(): void
    {
        self::checkSsl();
        self::setTimeZone();

        // Sanity check: ensure FrontController is available via autoload.
        if (!class_exists(FrontController::class)) {
            throw new RuntimeException(
                'FrontController not found. Ensure vendor/autoload.php is required by '
                . 'your bootstrap (run "composer install" and require autoload.php).'
            );
        }

        $frontController = new FrontController();
        $frontController->routeRequest();
    }

    /* ---------------------------------------------------------------------
     * Protected helpers
     * --------------------------------------------------------------------*/

    /**
     * Redirect to HTTPS when config key 'ssl_required' is truthy.
     *
     * Uses several indicators to detect an HTTPS request. If a redirect is
     * needed but headers were already sent, a warning is triggered instead of
     * attempting to send headers.
     */
    protected static function checkSsl(): void
    {
        $config = self::$config ?? [];

        if (empty($config['ssl_required'])) {
            return; // SSL not required.
        }

        // Determine if the current request looks secure.
        $isHttpsServerVar = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            && $_SERVER['HTTPS'] !== '0';

        $isPort443 = isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;

        $isForwardedProtoHttps = !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';

        $isHttps = $isHttpsServerVar || $isPort443 || $isForwardedProtoHttps;

        if ($isHttps) {
            return; // Already HTTPS, no redirect needed.
        }

        // Build host and request URI safely with fallbacks.
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $url = 'https://' . $host . $uri;

        if (headers_sent()) {
            $msg = 'SSL required but headers already sent; cannot redirect to %s';
            trigger_error(sprintf($msg, $url), E_USER_WARNING);

            return;
        }

        // Permanent redirect to HTTPS.
        header('Location: ' . $url, true, 301);
        exit;
    }

    /**
     * Set the PHP default timezone from config 'timezone' when provided.
     * Invalid values trigger a notice and are ignored.
     */
    protected static function setTimeZone(): void
    {
        $timezone = self::$config['timezone'] ?? null;

        if (!is_string($timezone) || $timezone === '') {
            return;
        }

        // Validate before setting; DateTimeZone will throw on invalid ids.
        try {
            new DateTimeZone($timezone);
            date_default_timezone_set($timezone);
        } catch (Exception $e) {
            trigger_error('Invalid timezone configured: ' . $timezone, E_USER_NOTICE);
        }
    }
}
