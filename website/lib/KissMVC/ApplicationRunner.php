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

use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * Runs the application with injectable seams for tests.
 */
final class ApplicationRunner
{
    private FrontControllerFactoryInterface $frontControllerFactory;
    private HeadersSentCheckerInterface $headersSentChecker;
    private HeaderEmitterInterface $headerEmitter;
    private RedirectTerminatorInterface $redirectTerminator;

    public function __construct(?ApplicationRunnerOptions $options = null)
    {
        $options ??= new ApplicationRunnerOptions();

        $this->frontControllerFactory = $options->frontControllerFactory
                                        ?? new DefaultFrontControllerFactory();

        $this->headersSentChecker = $options->headersSentChecker
                                    ?? new NativeHeadersSentChecker();

        $this->headerEmitter = $options->headerEmitter
                               ?? new NativeHeaderEmitter();

        $this->redirectTerminator = $options->redirectTerminator
                                    ?? new ExitRedirectTerminator();
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

        $frontController = $this->frontControllerFactory->create();
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
        return $this->headersSentChecker->headersSent();
    }

    private function emitHeader(string $header, bool $replace = true, ?int $responseCode = null): void
    {
        $this->headerEmitter->emit($header, $replace, $responseCode);
    }

    private function redirectToHttps(string $url): void
    {
        $this->redirectTerminator->terminate($url);
    }
}
