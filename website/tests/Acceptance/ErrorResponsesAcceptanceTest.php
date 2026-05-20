<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use FilesystemIterator;
use KissMVC\Application;
use KissMVC\Controller;
use KissMVC\FrontController;
use KissMVC\FrontControllerOptions;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Tests\Support\FixedHeadersSentChecker;
use Tests\Support\FixedRequestParametersProvider;
use Tests\Support\FixedRouteResolver;
use Tests\Support\RecordingHeaderEmitter;

final class ErrorResponsesAcceptanceTest extends TestCase
{
    private string $tempDir = '';
    private string $layoutsDir = '';
    private string $viewsDir = '';
    private array $serverBackup = [];

    protected function setUp(): void
    {
        self::resetRegistry();
        $this->serverBackup = $_SERVER;

        $this->tempDir = $this->createTempDirectory();
        $this->layoutsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'layouts';
        $this->viewsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'views';

        mkdir($this->layoutsDir, 0777, true);
        mkdir($this->viewsDir, 0777, true);

        Application::setRegistryItem('layouts_directory', $this->layoutsDir);
        Application::setRegistryItem('views_directory', $this->viewsDir);
    }

    protected function tearDown(): void
    {
        self::resetRegistry();
        $this->restoreServerVariables($this->serverBackup);
        $this->removeTempDirectory($this->tempDir);
    }

    /** KMVC-006-S001 */
    public function testKMVC006S001UnresolvedRouteProducesA404Response(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['missing-route']),
            new FixedRouteResolver(null),
            $headers
        );

        $output = $this->runRouteRequest($options, ['SERVER_PROTOCOL' => 'HTTP/2']);

        self::assertStringContainsString('404 Not Found', $output);
        self::assertSame(
            [
                ['HTTP/2 404 Not Found', true, 404],
                ['Status: 404 Not Found', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-006-S002 */
    public function testKMVC006S002Configured404ViewIsRenderedWhenAvailable(): void
    {
        $this->writeViewFixture('404.php', <<<'PHP'
<?php
echo 'configured-404-view';
PHP
        );

        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['missing-route']),
            new FixedRouteResolver(null),
            $headers
        );

        $output = $this->runRouteRequest($options);

        self::assertStringContainsString('configured-404-view', $output);
        self::assertStringNotContainsString('404 Not Found', $output);
        self::assertSame(
            [
                ['HTTP/1.1 404 Not Found', true, 404],
                ['Status: 404 Not Found', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-006-S003 */
    public function testKMVC006S003SafeFallback404MessageIsRenderedWhenNo404ViewIsAvailable(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['missing-route']),
            new FixedRouteResolver(null),
            $headers
        );

        $output = $this->runRouteRequest($options);

        self::assertStringContainsString('404 Not Found', $output);
        self::assertSame(
            [
                ['HTTP/1.1 404 Not Found', true, 404],
                ['Status: 404 Not Found', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-006-S004 */
    public function testKMVC006S004ControllerFailureProducesA500Response(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['boom']),
            new FixedRouteResolver($this->createThrowingController('controller-failure')),
            $headers
        );

        $output = $this->runRouteRequest($options, ['SERVER_PROTOCOL' => 'HTTP/2']);

        self::assertStringContainsString('An internal error occurred', $output);
        self::assertSame(
            [
                ['HTTP/2 500 Internal Server Error', true, 500],
                ['Status: 500 Internal Server Error', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-006-S005 */
    public function testKMVC006S005Configured500ViewIsRenderedWhenAvailable(): void
    {
        $this->writeViewFixture('500.php', <<<'PHP'
<?php
echo 'configured-500-view';
PHP
        );

        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['boom']),
            new FixedRouteResolver($this->createThrowingController('controller-failure')),
            $headers
        );

        $output = $this->runRouteRequest($options);

        self::assertStringContainsString('configured-500-view', $output);
        self::assertStringNotContainsString('An internal error occurred. Please try again later.', $output);
        self::assertSame(
            [
                ['HTTP/1.1 500 Internal Server Error', true, 500],
                ['Status: 500 Internal Server Error', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-006-S006 */
    public function testKMVC006S006SafeFallback500MessageIsRenderedWhenNo500ViewIsAvailable(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['boom']),
            new FixedRouteResolver($this->createThrowingController('controller-failure')),
            $headers
        );

        $output = $this->runRouteRequest($options);

        self::assertStringContainsString('An internal error occurred. Please try again later.', $output);
        self::assertSame(
            [
                ['HTTP/1.1 500 Internal Server Error', true, 500],
                ['Status: 500 Internal Server Error', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-006-S007 */
    public function testKMVC006S007ErrorResponsesDoNotExposeInternalExceptionDetailsByDefault(): void
    {
        $secret = 'secret-' . uniqid('', true);
        $headers = new RecordingHeaderEmitter();
        $options = $this->createOptions(
            new FixedRequestParametersProvider(['boom']),
            new FixedRouteResolver($this->createThrowingController($secret)),
            $headers
        );

        $output = $this->runRouteRequest($options);

        self::assertStringNotContainsString($secret, $output);
        self::assertStringContainsString('An internal error occurred. Please try again later.', $output);
        self::assertSame(
            [
                ['HTTP/1.1 500 Internal Server Error', true, 500],
                ['Status: 500 Internal Server Error', true, null],
            ],
            $headers->headers
        );
    }

    private function createOptions(
        FixedRequestParametersProvider $requestParametersProvider,
        FixedRouteResolver $routeResolver,
        RecordingHeaderEmitter $headerEmitter
    ): FrontControllerOptions {
        $options = new FrontControllerOptions();
        $options->requestParametersProvider = $requestParametersProvider;
        $options->routeResolver = $routeResolver;
        $options->headersSentChecker = new FixedHeadersSentChecker(false);
        $options->headerEmitter = $headerEmitter;

        return $options;
    }

    private function runRouteRequest(FrontControllerOptions $options, array $serverOverrides = []): string
    {
        $server = $this->withServerVariables($serverOverrides + [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ]);

        try
        {
            ob_start();
            (new FrontController($options))->routeRequest();

            return (string) ob_get_clean();
        }
        finally
        {
            $this->restoreServerVariables($server);
        }
    }

    private function writeViewFixture(string $fileName, string $contents): void
    {
        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . $fileName, $contents);
    }

    private function createThrowingController(string $message): Controller
    {
        return new class($message) extends Controller
        {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(private string $message)
            {
            }

            public function execute(): void
            {
                throw new RuntimeException($this->message);
            }
        };
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-error-responses-' . uniqid('', true);
        mkdir($directory, 0777, true);

        return $directory;
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
        $snapshot = $_SERVER;

        foreach($overrides as $key => $value)
        {
            if($value === null)
            {
                unset($_SERVER[$key]);
                continue;
            }

            $_SERVER[$key] = $value;
        }

        return $snapshot;
    }

    private function restoreServerVariables(array $snapshot): void
    {
        $_SERVER = $snapshot;
    }
}
