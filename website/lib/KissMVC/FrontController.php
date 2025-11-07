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
 */

namespace KissMVC;

use Throwable;

/**
 * FrontController
 *
 * Routes an incoming HTTP request to the appropriate Controller.
 *
 * Modernized for PHP 7.4:
 *  - strict types
 *  - typed method signatures
 *  - safer access to $_SERVER values with fallbacks
 *  - explicit, small helper methods
 *  - improved documentation for new contributors (KISS + Clean Code)
 *
 * Behavior notes:
 *  - This class relies on the legacy `routeToController()` function which is
 *    expected to be provided by a routes file. The original code used
 *    `require_once(APP_PATH . '/config/routes.php')`. To be robust we try to
 *    load the same path but warn instead of fatally failing when APP_PATH or
 *    the routes file is missing. See `website/application/config/routes.php`.
 *
 * See AGENTS.md for development and testing conventions used in this repo.
 */

// Attempt to load the project's routing definitions. This preserves the
// original behavior while avoiding a hard fatal when APP_PATH is missing.
if(defined('APP_PATH'))
{
    $routesFile = rtrim(APP_PATH, "\/") . "/Config/routes.php";

    if(is_readable($routesFile))
    {
        require_once $routesFile;
    }
    else
    {
        trigger_error('Routes file not found: ' . $routesFile, E_USER_WARNING);
    }
}
else
{
    trigger_error('APP_PATH is not defined; routes not loaded', E_USER_WARNING);
}

class FrontController
{
    private const DEFAULT_CONTROLLER = 'default';
    private const HTTP_404_VIEW      = '404.php';
    private const HTTP_500_VIEW      = '500.php';

    /**
     * Route the current HTTP request to the appropriate controller and render.
     *
     * High level contract:
     *  - Read URL segments via getRequestParameters()
     *  - Use routeToController() (legacy function) to obtain a Controller or
     *    null when not found
     *  - Provide the controller with request params, invoke execute(), then
     *    render the layout
     *
     * Error handling:
     *  - Missing controller -> display 404 view
     *  - Exceptions thrown by controllers -> display 500 view
     */
    public function routeRequest(): void
    {
        $requestParameters = $this->getRequestParameters();
        $controller = null;

        if($requestParameters === null)
        {
            $controller = function_exists('routeToController')
                ? routeToController(self::DEFAULT_CONTROLLER)
                : null;
        }
        else
        {
            $pageName = $requestParameters[0] ?? ''; // first segment
            $controller = function_exists('routeToController') ? routeToController($pageName) : null;

            // Remove the page name from the array and reindex.
            array_shift($requestParameters);
        }

        if($controller === null)
        {
            $this->display404Page();

            return;
        }

        try
        {
            // Provide parameters, execute business logic, then render.
            $controller->setRequestParameters($requestParameters ?? []);
            $controller->execute();

            ob_start();
            $controller->renderLayout();
            ob_end_flush();
        }
        catch(Throwable $e)
        {
            // Discard any partial output produced after we started buffering.
            ob_end_clean();

            // Render a friendly 500 page and stop.
            $this->display500Page($e);
        }
    }

    /* ------------------------------------------------------------------
     * Private helpers
     * ------------------------------------------------------------------ */

    /**
     * Parse and return URL segments as an ordered array of strings.
     *
     * Returns null when there are no segments (root request).
     */
    private function getRequestParameters(): ?array
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = $scriptName !== '' ? dirname($scriptName) : '';

        // Remove script directory from the request path when needed.
        if($scriptDir !== '' && $scriptDir !== '/')
        {
            $request = str_replace($scriptDir, '', $requestUri);
        }
        else
        {
            $request = $requestUri;
        }

        $request = trim((string)$request, '/');

        if(strlen($request) > 0)
        {
            return $this->urlSegments($request);
        }

        return null;
    }

    /**
     * Display the project's configured 404 page. Falls back to a minimal
     * message when the view file cannot be found or read.
     */
    private function display404Page(): void
    {
        $_SERVER['REDIRECT_STATUS'] = 404;
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';

        if(!headers_sent())
        {
            header($protocol . ' 404 Not Found', true, 404);
            header('Status: 404 Not Found');
        }

        $dir = Application::getRegistryItem('views_directory') ?? '';
        $view = rtrim((string)$dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::HTTP_404_VIEW;

        if(is_readable($view))
        {
            ob_start();
            include $view;
            ob_end_flush();

            return;
        }

        // Minimal fallback when no view file exists.
        echo '404 Not Found';
    }

    /**
     * Display a 500 error page. Attempts to include the configured 500 view
     * and otherwise prints a safe fallback. The throwable is optionally
     * accepted for logging or debug display by a custom 500 view.
     */
    private function display500Page(?Throwable $e = null): void
    {
        $_SERVER['REDIRECT_STATUS'] = 500;
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';

        if(!headers_sent())
        {
            header($protocol . ' 500 Internal Server Error', true, 500);
            header('Status: 500 Internal Server Error');
        }

        $dir = Application::getRegistryItem('views_directory') ?? '';
        $view = rtrim((string)$dir, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . self::HTTP_500_VIEW;

        if(is_readable($view))
        {
            // Make the throwable available to the 500 view if it wants it.
            $exception = $e; // intentionally short-lived local for templates.

            ob_start();
            include $view;
            ob_end_flush();

            return;
        }

        // Safe fallback message. Avoid exposing internals by default.
        echo 'An internal error occurred. Please try again later.';
    }

    /**
     * Split the request path into segments and strip query strings. Empty
     * segments are skipped.
     */
    private function urlSegments(string $request): array
    {
        $requestParams = explode('/', $request);
        $params = [];

        foreach($requestParams as $param)
        {
            if($param === '')
            {
                continue; // skip empty segments
            }

            $arr = explode('?', $param, 2);

            if(isset($arr[0]) && $arr[0] !== '')
            {
                $params[] = $arr[0];
            }
        }

        return $params;
    }
}
