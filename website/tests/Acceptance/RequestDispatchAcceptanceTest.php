<?php

declare(strict_types=1);

namespace Tests\Acceptance;

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

final class RequestDispatchAcceptanceTest extends TestCase
{
    private string $tempDir = '';
    private string $layoutsDir = '';
    private string $viewsDir = '';

    protected function setUp(): void
    {
        self::resetRegistry();

        $this->tempDir = $this->createTempDirectory();
        $this->layoutsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'layouts';
        $this->viewsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'views';

        mkdir($this->layoutsDir, 0777, true);
        mkdir($this->viewsDir, 0777, true);

        $this->writeDefaultLayout();
        $this->writeErrorViews();

        Application::setRegistryItem('layouts_directory', $this->layoutsDir);
        Application::setRegistryItem('views_directory', $this->viewsDir);
    }

    protected function tearDown(): void
    {
        self::resetRegistry();
        $this->removeTempDirectory($this->tempDir);
    }

    /** KMVC-002-S001 */
    public function testKMVC002S001RootRequestDispatchesToTheDefaultPage(): void
    {
        $output = $this->runRouteRequest([
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
        ]);

        self::assertStringContainsString('title:Index|params:', $output);
    }

    /** KMVC-002-S002 */
    public function testKMVC002S002OneSegmentRequestSelectsTheMatchingRoute(): void
    {
        $output = $this->runRouteRequest([
            'REQUEST_URI' => '/page-with-parameters',
            'SCRIPT_NAME' => '/index.php',
        ]);

        self::assertStringContainsString('title:Page with Parameters|params:', $output);
    }

    /** KMVC-002-S003 */
    public function testKMVC002S003RemainingUrlSegmentsArePassedToTheSelectedController(): void
    {
        $output = $this->runRouteRequest([
            'REQUEST_URI' => '/page-with-parameters/abc/123/xyz',
            'SCRIPT_NAME' => '/index.php',
        ]);

        self::assertStringContainsString('title:Page with Parameters|params:abc,123,xyz', $output);
    }

    /** KMVC-002-S004 */
    public function testKMVC002S004QueryStringsDoNotBecomeRequestParameters(): void
    {
        $output = $this->runRouteRequest([
            'REQUEST_URI' => '/page-with-parameters/abc/123/xyz?ignored=value',
            'SCRIPT_NAME' => '/index.php',
        ]);

        self::assertStringContainsString('title:Page with Parameters|params:abc,123,xyz', $output);
        self::assertStringNotContainsString('ignored=value', $output);
    }

    /** Verifies FrontController honors an injected request-parameters provider. */
    public function testRouteRequestHonorsAnInjectedRequestParametersProvider(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = new FrontControllerOptions();
        $options->requestParametersProvider = new FixedRequestParametersProvider(['custom-route', 'alpha', 'beta']);
        $options->headersSentChecker = new FixedHeadersSentChecker(false);
        $options->headerEmitter = $headers;

        $output = $this->runRouteRequest([
            'REQUEST_URI' => '/page-with-parameters/alpha/beta',
            'SCRIPT_NAME' => '/index.php',
        ], $options);

        self::assertStringContainsString('404 view:404', $output);
        self::assertStringNotContainsString('title:Page with Parameters|params:alpha,beta', $output);
        self::assertSame(
            [
                ['HTTP/1.1 404 Not Found', true, 404],
                ['Status: 404 Not Found', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-002-S005 */
    public function testKMVC002S005UnknownRouteProducesA404ResponsePath(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = new FrontControllerOptions();
        $options->requestParametersProvider = new FixedRequestParametersProvider(['missing-route']);
        $options->routeResolver = new FixedRouteResolver(null);
        $options->headersSentChecker = new FixedHeadersSentChecker(false);
        $options->headerEmitter = $headers;

        $output = $this->runRouteRequest([], $options);

        self::assertStringContainsString('404 view:404', $output);
        self::assertSame(
            [
                ['HTTP/1.1 404 Not Found', true, 404],
                ['Status: 404 Not Found', true, null],
            ],
            $headers->headers
        );
    }

    /** KMVC-002-S006 */
    public function testKMVC002S006ControllerFailureProducesA500ResponsePath(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = new FrontControllerOptions();
        $options->requestParametersProvider = new FixedRequestParametersProvider(['boom']);
        $options->routeResolver = new FixedRouteResolver($this->createThrowingController());
        $options->headersSentChecker = new FixedHeadersSentChecker(false);
        $options->headerEmitter = $headers;

        $output = $this->runRouteRequest([], $options);

        self::assertStringContainsString('500 view:boom', $output);
        self::assertSame(
            [
                ['HTTP/1.1 500 Internal Server Error', true, 500],
                ['Status: 500 Internal Server Error', true, null],
            ],
            $headers->headers
        );
    }

    private function runRouteRequest(array $serverOverrides, ?FrontControllerOptions $options = null): string
    {
        $serverOverrides['SERVER_PROTOCOL'] ??= 'HTTP/1.1';
        $serverOverrides['REDIRECT_STATUS'] ??= null;

        $server = $this->withServerVariables($serverOverrides);

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

    private function writeDefaultLayout(): void
    {
        file_put_contents(
            $this->layoutsDir . DIRECTORY_SEPARATOR . 'default.php',
            <<<'PHP'
<?php
echo 'title:' . $this->getPageTitle() . '|params:' . implode(',', $this->getRequestParameters());
PHP
        );
    }

    private function writeErrorViews(): void
    {
        file_put_contents(
            $this->viewsDir . DIRECTORY_SEPARATOR . '404.php',
            <<<'PHP'
<?php
echo '404 view:' . ($_SERVER['REDIRECT_STATUS'] ?? '');
PHP
        );

        file_put_contents(
            $this->viewsDir . DIRECTORY_SEPARATOR . '500.php',
            <<<'PHP'
<?php
$message = isset($exception) && $exception instanceof Throwable ? $exception->getMessage() : 'none';
echo '500 view:' . $message;
PHP
        );
    }

    private function createThrowingController(): Controller
    {
        return new class extends Controller
        {
            public function execute(): void
            {
                throw new RuntimeException('boom');
            }
        };
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-request-dispatch-' . uniqid('', true);
        mkdir($directory, 0777, true);

        return $directory;
    }

    private function removeTempDirectory(string $directory): void
    {
        if(!is_dir($directory))
        {
            return;
        }

        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

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
        $snapshot = [];

        foreach($overrides as $key => $value)
        {
            $snapshot[$key] = array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null;
            $_SERVER[$key] = $value;
        }

        return $snapshot;
    }

    private function restoreServerVariables(array $snapshot): void
    {
        foreach($snapshot as $key => $value)
        {
            if($value === null)
            {
                unset($_SERVER[$key]);
                continue;
            }

            $_SERVER[$key] = $value;
        }
    }
}
