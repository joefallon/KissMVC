<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use KissMVC\Application;
use KissMVC\ApplicationRunner;
use KissMVC\ApplicationRunnerOptions;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\Support\FixedHeadersSentChecker;
use Tests\Support\RecordingFrontController;
use Tests\Support\RecordingFrontControllerFactory;
use Tests\Support\RecordingHeaderEmitter;
use Tests\Support\RecordingRedirectTerminator;

final class ApplicationRunnerAcceptanceTest extends TestCase
{
    private string $originalTimezone = '';

    protected function setUp(): void
    {
        self::resetRegistry();
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        self::resetRegistry();
    }

    /** KMVC-005-S001 */
    public function testKMVC005S001RunningTheApplicationChecksWhetherSslRedirectionIsRequired(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();

        $this->runApplication([
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
        ], false, false, null, $frontController, $headerEmitter, $redirectTerminator);

        self::assertTrue($frontController->wasRouted);
        self::assertSame([], $headerEmitter->headers);
        self::assertSame([], $redirectTerminator->urls);
    }

    /** KMVC-005-S002 */
    public function testKMVC005S002RunningTheApplicationAppliesAConfiguredTimezone(): void
    {
        $frontController = new RecordingFrontController();
        $originalTimezone = date_default_timezone_get();

        Application::setRegistryItem('timezone', 'America/Los_Angeles');

        try
        {
            $this->runApplication([
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
            ], false, false, null, $frontController);

            self::assertSame('America/Los_Angeles', date_default_timezone_get());
            self::assertTrue($frontController->wasRouted);
        }
        finally
        {
            date_default_timezone_set($originalTimezone);
        }
    }

    /** KMVC-005-S003 */
    public function testKMVC005S003RunningTheApplicationDispatchesThroughTheFrontController(): void
    {
        $frontController = new RecordingFrontController();

        $this->runApplication([
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
        ], false, false, null, $frontController);

        self::assertTrue($frontController->wasRouted);
    }

    /** KMVC-005-S004 */
    public function testKMVC005S004WhenSslIsNotRequiredNoRedirectOccurs(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();

        $this->runApplication([
            'HTTPS' => null,
            'HTTP_X_FORWARDED_PROTO' => null,
            'SERVER_PORT' => null,
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/plain',
        ], false, false, null, $frontController, $headerEmitter, $redirectTerminator);

        self::assertTrue($frontController->wasRouted);
        self::assertSame([], $headerEmitter->headers);
        self::assertSame([], $redirectTerminator->urls);
    }

    /** KMVC-005-S005 */
    public function testKMVC005S005WhenSslIsRequiredAndTheRequestIsAlreadySecureNoRedirectOccurs(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();

        $this->runApplication([
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/secure',
        ], true, false, null, $frontController, $headerEmitter, $redirectTerminator);

        self::assertTrue($frontController->wasRouted);
        self::assertSame([], $headerEmitter->headers);
        self::assertSame([], $redirectTerminator->urls);
    }

    /** KMVC-005-S006 */
    public function testKMVC005S006WhenSslIsRequiredAndTheRequestIsNotSecureTheApplicationRedirectsToHttps(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();

        $this->runApplication([
            'HTTPS' => null,
            'HTTP_X_FORWARDED_PROTO' => null,
            'SERVER_PORT' => null,
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/secure',
        ], true, false, null, $frontController, $headerEmitter, $redirectTerminator);

        self::assertSame(
            [['Location: https://example.test/secure', true, 301]],
            $headerEmitter->headers
        );
        self::assertSame(['https://example.test/secure'], $redirectTerminator->urls);
    }

    // Mutation-hardening variant of KMVC-005-S005.
    /** KMVC-005-S005 */
    public function testKMVC005S005WhenSslIsRequiredAForwardedProtoOfHttpsIsTreatedCaseInsensitively(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();

        $this->runApplication([
            'HTTPS' => null,
            'HTTP_X_FORWARDED_PROTO' => 'HTTPS',
            'SERVER_PORT' => null,
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/secure',
        ], true, false, null, $frontController, $headerEmitter, $redirectTerminator);

        self::assertTrue($frontController->wasRouted);
        self::assertSame([], $headerEmitter->headers);
        self::assertSame([], $redirectTerminator->urls);
    }

    // Mutation-hardening variant of KMVC-005-S006.
    /** KMVC-005-S006 */
    public function testKMVC005S006WhenSslIsRequiredHostHeaderTakesPrecedenceOverServerNameForRedirects(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();

        $this->runApplication([
            'HTTPS' => null,
            'HTTP_X_FORWARDED_PROTO' => null,
            'SERVER_PORT' => null,
            'HTTP_HOST' => 'host-header.test',
            'SERVER_NAME' => 'server-name.test',
            'REQUEST_URI' => '/secure',
        ], true, false, null, $frontController, $headerEmitter, $redirectTerminator);

        self::assertSame(
            [['Location: https://host-header.test/secure', true, 301]],
            $headerEmitter->headers
        );
        self::assertSame(['https://host-header.test/secure'], $redirectTerminator->urls);
    }

    /** KMVC-005-S007 */
    public function testKMVC005S007WhenHeadersWereAlreadySentSslRedirectionCannotBePerformed(): void
    {
        $frontController = new RecordingFrontController();
        $headerEmitter = new RecordingHeaderEmitter();
        $redirectTerminator = new RecordingRedirectTerminator();
        $errors = [];

        set_error_handler(static function (int $severity, string $message) use (&$errors): bool {
            $errors[] = [$severity, $message];

            return true;
        });

        try
        {
            $this->runApplication([
                'HTTPS' => null,
                'HTTP_X_FORWARDED_PROTO' => null,
                'SERVER_PORT' => null,
                'HTTP_HOST' => 'example.test',
                'REQUEST_URI' => '/secure',
            ], true, true, null, $frontController, $headerEmitter, $redirectTerminator);
        }
        finally
        {
            restore_error_handler();
        }

        self::assertTrue($frontController->wasRouted);
        self::assertSame([], $headerEmitter->headers);
        self::assertSame([], $redirectTerminator->urls);
        self::assertNotEmpty($errors);
        self::assertSame(E_USER_WARNING, $errors[0][0]);
        self::assertStringContainsString('SSL required but headers already sent', $errors[0][1]);
    }

    /** KMVC-005-S008 */
    public function testKMVC005S008InvalidTimezoneConfigurationIsReportedWithoutStoppingRequestDispatch(): void
    {
        $frontController = new RecordingFrontController();
        $errors = [];

        Application::setRegistryItem('timezone', 'not-a-real-timezone');

        set_error_handler(static function (int $severity, string $message) use (&$errors): bool {
            $errors[] = [$severity, $message];

            return true;
        });

        try
        {
            $this->runApplication([
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => '/index.php',
            ], false, false, null, $frontController);
        }
        finally
        {
            restore_error_handler();
        }

        self::assertTrue($frontController->wasRouted);
        self::assertNotEmpty($errors);
        self::assertSame(E_USER_NOTICE, $errors[0][0]);
        self::assertSame('Invalid timezone configured: not-a-real-timezone', $errors[0][1]);
    }

    private function runApplication(
        array $serverOverrides,
        bool $sslRequired,
        bool $headersSent,
        ?string $timezone,
        RecordingFrontController $frontController,
        ?RecordingHeaderEmitter $headerEmitter = null,
        ?RecordingRedirectTerminator $redirectTerminator = null
    ): void {
        $server = $this->withServerVariables($serverOverrides);
        Application::setRegistryItem('ssl_required', $sslRequired);

        if($timezone !== null)
        {
            Application::setRegistryItem('timezone', $timezone);
        }

        $options = new ApplicationRunnerOptions();
        $options->frontControllerFactory = new RecordingFrontControllerFactory($frontController);
        $options->headersSentChecker = new FixedHeadersSentChecker($headersSent);

        if($headerEmitter !== null)
        {
            $options->headerEmitter = $headerEmitter;
        }

        if($redirectTerminator !== null)
        {
            $options->redirectTerminator = $redirectTerminator;
        }

        try
        {
            ob_start();
            (new ApplicationRunner($options))->run();
        }
        finally
        {
            if(ob_get_level() > 0)
            {
                ob_end_clean();
            }

            $this->restoreServerVariables($server);
        }
    }

    private function withServerVariables(array $overrides): array
    {
        $original = $_SERVER;
        foreach($overrides as $key => $value)
        {
            if($value === null)
            {
                unset($_SERVER[$key]);
                continue;
            }

            $_SERVER[$key] = $value;
        }

        return $original;
    }

    private function restoreServerVariables(array $original): void
    {
        $_SERVER = $original;
    }

    private static function resetRegistry(): void
    {
        $property = new ReflectionProperty(Application::class, 'config');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
