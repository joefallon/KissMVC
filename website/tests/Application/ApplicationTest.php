<?php
declare(strict_types=1);

namespace Tests\Application;

use FilesystemIterator;
use KissMVC\Application;
use KissMVC\ApplicationBuilder;
use KissMVC\FrontController;
use KissMVC\ApplicationRunner;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class ApplicationTest extends TestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        self::resetRegistry();
        $this->tempDir = $this->createTempDirectory();
    }

    protected function tearDown(): void
    {
        self::resetRegistry();
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

        try
        {
            (new ApplicationBuilder())->build()->setTimeZone();

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
            (new ApplicationBuilder())->build()->setTimeZone();

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
            (new ApplicationBuilder())->build()->setTimeZone();

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
            (new ApplicationBuilder())->build()->setTimeZone();

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
            (new ApplicationBuilder())->build()->setTimeZone();

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
        $headers = [];
        $redirects = [];
        Application::setRegistryItem('ssl_required', false);

        try
        {
            $this->createApplicationRunner(false, $headers, $redirects)->checkSsl();

            self::assertSame([], $headers);
            self::assertSame([], $redirects);
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
        $headers = [];
        $redirects = [];
        Application::setRegistryItem('ssl_required', true);

        try
        {
            $this->createApplicationRunner(false, $headers, $redirects)->checkSsl();

            self::assertSame([], $headers);
            self::assertSame([], $redirects);
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
        $headers = [];
        $redirects = [];
        Application::setRegistryItem('ssl_required', true);

        try
        {
            $this->createApplicationRunner(false, $headers, $redirects)->checkSsl();

            self::assertSame([], $headers);
            self::assertSame([], $redirects);
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
        $headers = [];
        $redirects = [];
        Application::setRegistryItem('ssl_required', true);

        try
        {
            $this->createApplicationRunner(false, $headers, $redirects)->checkSsl();

            self::assertSame([], $headers);
            self::assertSame([], $redirects);
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    public function testCheckSslRedirectsToHttpsWhenTheRequestIsNotSecure(): void
    {
        $server = $this->withServerVariables([
            'SERVER_NAME' => 'fallback.test',
            'REQUEST_URI' => '/secure',
        ]);
        $headers = [];
        $redirects = [];
        Application::setRegistryItem('ssl_required', true);

        try
        {
            $this->createApplicationRunner(false, $headers, $redirects)->checkSsl();

            self::assertSame([
                ['Location: https://fallback.test/secure', true, 301],
            ], $headers);
            self::assertSame(['https://fallback.test/secure'], $redirects);
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    public function testCheckSslTriggersWarningWhenHeadersWereAlreadySent(): void
    {
        $server = $this->withServerVariables([
            'SERVER_NAME' => 'fallback.test',
            'REQUEST_URI' => '/secure',
        ]);
        $headers = [];
        $redirects = [];
        Application::setRegistryItem('ssl_required', true);
        $errors = [];

        set_error_handler(static function (int $severity, string $message) use (&$errors): bool {
            $errors[] = [$severity, $message];

            return true;
        });

        try
        {
            $this->createApplicationRunner(true, $headers, $redirects)->checkSsl();
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
        self::assertSame([], $headers);
        self::assertSame([], $redirects);
    }

    public function testRunUsesAnInjectedFrontControllerFactory(): void
    {
        $frontController = new class extends FrontController
        {
            public bool $wasRouted = false;

            public function routeRequest(): void
            {
                $this->wasRouted = true;
            }
        };

        Application::setRegistryItem('ssl_required', false);
        Application::run(static fn (): FrontController => $frontController);

        self::assertTrue($frontController->wasRouted);
    }

    public function testRunUsesAnInjectedApplicationBuilder(): void
    {
        $frontController = new class extends FrontController
        {
            public bool $wasRouted = false;

            public function routeRequest(): void
            {
                $this->wasRouted = true;
            }
        };

        Application::setRegistryItem('ssl_required', false);
        $builder = (new ApplicationBuilder())->withFrontControllerFactory(
            static fn (): FrontController => $frontController
        );

        Application::run($builder);

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
            <<<'PHP'
<?php
echo 'layout:' . $this->getPageTitle();
PHP
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

    private function createApplicationRunner(
        bool $headersSent,
        array &$headers,
        array &$redirects
    ): ApplicationRunner {
        return (new ApplicationBuilder())
            ->withHeadersSentChecker(static fn (): bool => $headersSent)
            ->withHeaderEmitter(static function (
                string $header,
                bool $replace = true,
                ?int $responseCode = null
            ) use (&$headers): void {
                $headers[] = [$header, $replace, $responseCode];
            })
            ->withRedirectTerminator(static function (string $url) use (&$redirects): void {
                $redirects[] = $url;
            })
            ->build();
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
