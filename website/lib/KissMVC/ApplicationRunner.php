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
 */

namespace KissMVC;

use Closure;
use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * Runs the application with injectable seams for tests.
 */
final class ApplicationRunner
{
    private ?Closure $frontControllerFactory;
    private Closure $headersSentChecker;
    private Closure $headerEmitter;
    private Closure $redirectTerminator;

    public function __construct(
        ?Closure $frontControllerFactory = null,
        ?Closure $headersSentChecker = null,
        ?Closure $headerEmitter = null,
        ?Closure $redirectTerminator = null
    ) {
        $this->frontControllerFactory = $frontControllerFactory;
        $this->headersSentChecker = $headersSentChecker ?? static fn (): bool => headers_sent();
        $this->headerEmitter = $headerEmitter ?? static function (
            string $header,
            bool $replace = true,
            ?int $responseCode = null
        ): void {
            if($responseCode === null)
            {
                header($header, $replace);

                return;
            }

            header($header, $replace, $responseCode);
        };
        $this->redirectTerminator = $redirectTerminator ?? static function (string $url): void {
            exit;
        };
    }

    public function run(): void
    {
        $this->checkSsl();
        $this->setTimeZone();

        if(!class_exists(FrontController::class))
        {
            throw new RuntimeException(
                'FrontController not found. Ensure vendor/autoload.php is required by '
                . 'your bootstrap (run "composer install" and require autoload.php).'
            );
        }

        $frontController = $this->frontControllerFactory !== null
            ? ($this->frontControllerFactory)()
            : new FrontController();
        $frontController->routeRequest();
    }

    public function checkSsl(): void
    {
        $sslRequired = Application::getRegistryItem('ssl_required');

        if(empty($sslRequired))
        {
            return;
        }

        $isHttpsServerVar = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                            && $_SERVER['HTTPS'] !== '0';
        $isPort443 = isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443;
        $isForwardedProtoHttps = !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
                                 && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';

        if($isHttpsServerVar || $isPort443 || $isForwardedProtoHttps)
        {
            return;
        }

        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $url = 'https://' . $host . $uri;

        if($this->headersWereSent())
        {
            $msg = 'SSL required but headers already sent; cannot redirect to %s';
            trigger_error(sprintf($msg, $url), E_USER_WARNING);

            return;
        }

        $this->emitHeader('Location: ' . $url, true, 301);
        $this->redirectToHttps($url);
    }

    public function setTimeZone(): void
    {
        $timezone = Application::getRegistryItem('timezone');

        if(!is_string($timezone) || $timezone === '')
        {
            return;
        }

        try
        {
            new DateTimeZone($timezone);
            date_default_timezone_set($timezone);
        }
        catch(Exception)
        {
            trigger_error('Invalid timezone configured: ' . $timezone, E_USER_NOTICE);
        }
    }

    private function headersWereSent(): bool
    {
        return (bool) ($this->headersSentChecker)();
    }

    private function emitHeader(string $header, bool $replace = true, ?int $responseCode = null): void
    {
        ($this->headerEmitter)($header, $replace, $responseCode);
    }

    private function redirectToHttps(string $url): void
    {
        ($this->redirectTerminator)($url);
    }
}
