<?php
declare(strict_types=1);

namespace Tests\Application;

use FilesystemIterator;
use KissMVC\Application;
use KissMVC\FrontController;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class ApplicationTest extends TestCase
{
    private string $tempDir = '';

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        self::resetRegistry();
        ApplicationTestHarness::resetHeaderCapture();
        $this->tempDir = $this->createTempDirectory();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function tearDown(): void
    {
        self::resetRegistry();
        ApplicationTestHarness::resetHeaderCapture();
        $this->removeTempDirectory($this->tempDir);
    }

    public function testLoadConfigurationAcceptsAFileThatReturnsAnArray(): void
    {
        $configFile = $this->writePhpFile("return ['alpha' => 'one', 'shared' => 'first'];");

        Application::loadConfiguration($configFile);

        self::assertSame('one', Application::getRegistryItem('alpha'));
        self::assertSame('first', Application::getRegistryItem('shared'));
    }

    public function testLoadConfigurationAcceptsAFileThatAssignsConfig(): void
    {
        $configFile = $this->writePhpFile("\$config = ['beta' => 'two', 'shared' => 'second'];");

        Application::loadConfiguration($configFile);

        self::assertSame('two', Application::getRegistryItem('beta'));
        self::assertSame('second', Application::getRegistryItem('shared'));
    }

    public function testLaterConfigurationLoadsMergeAndOverrideEarlierRegistryValues(): void
    {
        $firstConfig = $this->writePhpFile("return ['alpha' => 'one', 'shared' => 'first'];");
        $secondConfig = $this->writePhpFile("\$config = ['beta' => 'two', 'shared' => 'second'];");

        Application::loadConfiguration($firstConfig);
        Application::loadConfiguration($secondConfig);

        self::assertSame('one', Application::getRegistryItem('alpha'));
        self::assertSame('two', Application::getRegistryItem('beta'));
        self::assertSame('second', Application::getRegistryItem('shared'));
    }

    public function testGetRegistryItemReturnsNullForMissingKeys(): void
    {
        self::assertNull(Application::getRegistryItem('missing'));
    }

    public function testSetRegistryItemStoresAndReturnsValues(): void
    {
        $value = ['name' => 'fixture', 'count' => 3];

        Application::setRegistryItem('fixture', $value);

        self::assertSame($value, Application::getRegistryItem('fixture'));
    }

    public function testInvalidConfigFilesThrowRuntimeException(): void
    {
        $configFile = $this->writePhpFile('return 42;');

        $this->expectException(RuntimeException::class);

        Application::loadConfiguration($configFile);
    }

    public function testSetTimeZoneLeavesCurrentTimezoneAloneWhenNotConfigured(): void
    {
        $originalTimezone = date_default_timezone_get();
        Application::setRegistryItem('app_name', 'KissMVC');

        try
        {
            ApplicationTestHarness::invokeSetTimeZone();

            self::assertSame($originalTimezone, date_default_timezone_get());
        }
        finally
        {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testSetTimeZoneAppliesConfiguredTimezone(): void
    {
        $originalTimezone = date_default_timezone_get();
        Application::setRegistryItem('timezone', 'UTC');

        try
        {
            ApplicationTestHarness::invokeSetTimeZone();

            self::assertSame('UTC', date_default_timezone_get());
        }
        finally
        {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testSetTimeZoneIgnoresEmptyTimezoneString(): void
    {
        $originalTimezone = date_default_timezone_get();
        Application::setRegistryItem('timezone', '');

        try
        {
            ApplicationTestHarness::invokeSetTimeZone();

            self::assertSame($originalTimezone, date_default_timezone_get());
        }
        finally
        {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testSetTimeZoneIgnoresNonStringTimezoneValue(): void
    {
        $originalTimezone = date_default_timezone_get();
        Application::setRegistryItem('timezone', 123);

        try
        {
            ApplicationTestHarness::invokeSetTimeZone();

            self::assertSame($originalTimezone, date_default_timezone_get());
        }
        finally
        {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function testSetTimeZoneIgnoresInvalidTimezoneAndTriggersNotice(): void
    {
        $originalTimezone = date_default_timezone_get();
        $errors = [];
        Application::setRegistryItem('timezone', 'not-a-real-timezone');

        set_error_handler(static function (int $severity, string $message) use (&$errors): bool {
            $errors[] = [$severity, $message];

            return true;
        });

        try
        {
            ApplicationTestHarness::invokeSetTimeZone();

            self::assertSame($originalTimezone, date_default_timezone_get());
        }
        finally
        {
            restore_error_handler();
            date_default_timezone_set($originalTimezone);
        }

        self::assertNotEmpty($errors);
        self::assertSame(E_USER_NOTICE, $errors[0][0]);
        self::assertStringContainsString('Invalid timezone configured', $errors[0][1]);
    }

    public function testCheckSslReturnsWithoutRedirectWhenSslIsNotRequired(): void
    {
        $server = $this->withServerVariables([]);
        Application::setRegistryItem('ssl_required', false);
        $headersBefore = headers_list();

        try
        {
            ApplicationTestHarness::invokeCheckSsl();

            self::assertSame($headersBefore, headers_list());
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    public function testCheckSslReturnsWithoutRedirectWhenRequestIsAlreadyHttps(): void
    {
        $server = $this->withServerVariables([
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/secure',
        ]);
        Application::setRegistryItem('ssl_required', true);
        $headersBefore = headers_list();

        try
        {
            ApplicationTestHarness::invokeCheckSsl();

            self::assertSame($headersBefore, headers_list());
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    public function testCheckSslReturnsWithoutRedirectWhenForwardedProtoIsHttps(): void
    {
        $server = $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/secure',
        ]);
        Application::setRegistryItem('ssl_required', true);
        $headersBefore = headers_list();

        try
        {
            ApplicationTestHarness::invokeCheckSsl();

            self::assertSame($headersBefore, headers_list());
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    public function testCheckSslReturnsWithoutRedirectWhenServerPortIs443(): void
    {
        $server = $this->withServerVariables([
            'SERVER_PORT' => '443',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/secure',
        ]);
        Application::setRegistryItem('ssl_required', true);
        $headersBefore = headers_list();

        try
        {
            ApplicationTestHarness::invokeCheckSsl();

            self::assertSame($headersBefore, headers_list());
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCheckSslRedirectsToHttpsWhenTheRequestIsNotSecure(): void
    {
        $server = $this->withServerVariables([
            'SERVER_NAME' => 'fallback.test',
            'REQUEST_URI' => '/secure',
        ]);

        Application::setRegistryItem('ssl_required', true);

        ob_start();
        try
        {
            ApplicationRedirectHarness::invokeCheckSsl();
            self::fail('Expected RedirectIntercepted to be thrown');
        }
        catch(RedirectIntercepted $e)
        {
            self::assertSame('https://fallback.test/secure', $e->getUrl());
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

    public function testCheckSslTriggersWarningWhenHeadersWereAlreadySent(): void
    {
        $server = $this->withServerVariables([
            'SERVER_NAME' => 'fallback.test',
            'REQUEST_URI' => '/secure',
        ]);

        Application::setRegistryItem('ssl_required', true);
        ApplicationTestHarness::setHeadersSent(true);
        $errors = [];

        set_error_handler(static function (int $severity, string $message) use (&$errors): bool {
            $errors[] = [$severity, $message];

            return true;
        });

        try
        {
            ApplicationTestHarness::invokeCheckSsl();
        }
        finally
        {
            restore_error_handler();
            $this->restoreServerVariables($server);
        }

        self::assertNotEmpty($errors);
        self::assertSame(E_USER_WARNING, $errors[0][0]);
        self::assertStringContainsString(
            'SSL required but headers already sent; cannot redirect to https://fallback.test/secure',
            $errors[0][1]
        );
        self::assertSame([], ApplicationTestHarness::emittedHeaders());
    }

    public function testRunUsesAnInjectedFrontControllerFactory(): void
    {
        $frontController = new TestFrontControllerForRun();
        Application::setRegistryItem('ssl_required', false);

        Application::run(static fn (): TestFrontControllerForRun => $frontController);

        self::assertTrue($frontController->wasRouted);
    }

    public function testRunUsesTheDefaultFrontControllerWhenNoFactoryIsProvided(): void
    {
        $layoutsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'layouts';
        $viewsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'views';

        mkdir($layoutsDir, 0777, true);
        mkdir($viewsDir, 0777, true);

        file_put_contents(
            $layoutsDir . DIRECTORY_SEPARATOR . 'default.php',
            "<?php\necho 'layout:' . \$this->getPageTitle();\n"
        );

        $server = $this->withServerVariables([
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
        ]);
        Application::setRegistryItem('layouts_directory', $layoutsDir);
        Application::setRegistryItem('views_directory', $viewsDir);
        Application::setRegistryItem('ssl_required', false);

        try
        {
            ob_start();
            Application::run();
            $output = ob_get_clean();

            self::assertStringContainsString('layout:Index', (string) $output);
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-application-' . uniqid('', true);
        mkdir($directory, 0777, true);

        return $directory;
    }

    private function writePhpFile(string $body): string
    {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . uniqid('config_', true) . '.php';
        file_put_contents($path, "<?php\ndeclare(strict_types=1);\n" . $body . "\n");

        return $path;
    }

    private function removeTempDirectory(string $directory): void
    {
        if(!is_dir($directory))
        {
            return;
        }

        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach($items as $item)
        {
            if($item->isDir())
            {
                $this->removeTempDirectory($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }

    private static function resetRegistry(): void
    {
        $property = new ReflectionProperty(Application::class, 'config');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function withServerVariables(array $overrides): array
    {
        $original = $_SERVER;
        $_SERVER = array_merge($_SERVER, $overrides);

        return $original;
    }

    private function restoreServerVariables(array $original): void
    {
        $_SERVER = $original;
    }
}

final class ApplicationTestHarness extends Application
{
    private static bool $headersSent = false;
    private static array $emittedHeaders = [];

    public static function invokeCheckSsl(): void
    {
        parent::checkSsl();
    }

    public static function invokeSetTimeZone(): void
    {
        parent::setTimeZone();
    }

    public static function setHeadersSent(bool $headersSent): void
    {
        self::$headersSent = $headersSent;
    }

    public static function emittedHeaders(): array
    {
        return self::$emittedHeaders;
    }

    public static function resetHeaderCapture(): void
    {
        self::$headersSent = false;
        self::$emittedHeaders = [];
    }

    protected static function headersWereSent(): bool
    {
        return self::$headersSent;
    }

    protected static function emitHeader(string $header, bool $replace = true, ?int $responseCode = null): void
    {
        self::$emittedHeaders[] = [$header, $replace, $responseCode];
    }
}

final class ApplicationRedirectHarness extends Application
{
    public static function invokeCheckSsl(): void
    {
        parent::checkSsl();
    }

    protected static function beforeExitFromRedirect(string $url): void
    {
        throw new RedirectIntercepted($url);
    }
}

final class RedirectIntercepted extends RuntimeException
{
    public function __construct(private string $url)
    {
        parent::__construct($url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}

final class TestFrontControllerForRun extends FrontController
{
    public bool $wasRouted = false;

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function routeRequest(): void
    {
        $this->wasRouted = true;
    }
}
