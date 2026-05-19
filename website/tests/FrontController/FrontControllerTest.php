<?php
declare(strict_types=1);

namespace Tests\FrontController;

use FilesystemIterator;
use KissMVC\Application;
use KissMVC\Controller;
use KissMVC\FrontController;
use KissMVC\FrontControllerBuilder;
use KissMVC\FrontControllerOptions;
use Tests\Support\FixedHeadersSentChecker;
use Tests\Support\FixedRequestParametersProvider;
use Tests\Support\FixedRouteResolver;
use Tests\Support\RecordingHeaderEmitter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

final class FrontControllerTest extends TestCase
{
    private string $tempDir = '';
    private string $layoutsDir = '';
    private string $viewsDir = '';
    private array $serverBackup = [];

    /** @noinspection PhpMissingParentCallCommonInspection */
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
        Application::setRegistryItem('partials_directory', $this->viewsDir);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function tearDown(): void
    {
        self::resetRegistry();
        $this->restoreServerVariables($this->serverBackup);
        $this->removeTempDirectory($this->tempDir);
    }

    public function testRouteRequestRendersTheDefaultControllerForTheRootRoute(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->writeDefaultLayout();

        ob_start();
        (new FrontController())->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('layout:Index|params:', (string)$output);
    }

    public function testRouteRequestPassesRemainingParametersToThePageWithParametersController(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/subdir/page-with-parameters//abc/123/xyz/?foo=bar';
        $_SERVER['SCRIPT_NAME'] = '/subdir/index.php';

        $this->writeDefaultLayout();

        ob_start();
        (new FrontController())->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('layout:Page with Parameters|params:abc,123,xyz', (string)$output);
    }

    public function testRouteRequestDisplaysThe404ViewForUnknownRoutes(): void
    {
        $headers = new RecordingHeaderEmitter();
        $options = new FrontControllerOptions();
        $options->requestParametersProvider = new FixedRequestParametersProvider(['missing-route']);
        $options->routeResolver = new FixedRouteResolver(null);
        $options->headersSentChecker = new FixedHeadersSentChecker(false);
        $options->headerEmitter = $headers;

        $frontController = new FrontControllerBuilder($options)->build();

        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '404.php', <<<'PHP'
<?php
echo '404 view:' . ($_SERVER['REDIRECT_STATUS'] ?? '');
PHP
        );

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('404 view:404', (string)$output);
        self::assertSame([['HTTP/1.1 404 Not Found', true, 404], ['Status: 404 Not Found', true, null],],
            $headers->headers);
        self::assertSame(404, $_SERVER['REDIRECT_STATUS']);
    }

    public function testRouteRequestSkips404HeaderEmissionWhenHeadersWereSent(): void
    {
        $headers = new RecordingHeaderEmitter();

        $frontController = (new FrontControllerBuilder())->withRequestParametersProvider(new FixedRequestParametersProvider(['missing-route']))
                                                         ->withRouteResolver(new FixedRouteResolver(null))
                                                         ->withHeadersSentChecker(new FixedHeadersSentChecker(true))
                                                         ->withHeaderEmitter($headers)->build();

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertSame('404 Not Found', trim((string)$output));
        self::assertSame([], $headers->headers);
    }

    public function testRouteRequestDisplaysThe500ViewForControllerExceptions(): void
    {
        $headers = new RecordingHeaderEmitter();

        $frontController = (new FrontControllerBuilder())->withRequestParametersProvider(new FixedRequestParametersProvider(['boom']))
                                                         ->withRouteResolver(new FixedRouteResolver($this->createThrowingController()))
                                                         ->withHeadersSentChecker(new FixedHeadersSentChecker(false))
                                                         ->withHeaderEmitter($headers)->build();

        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '500.php', <<<'PHP'
<?php
$message = isset($exception) && $exception instanceof Throwable ? $exception->getMessage() : 'none';
echo '500 view:' . $message;
PHP
        );

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('500 view:boom', (string)$output);
        self::assertSame([['HTTP/1.1 500 Internal Server Error', true, 500],
                          ['Status: 500 Internal Server Error', true, null],], $headers->headers);
        self::assertSame(500, $_SERVER['REDIRECT_STATUS']);
    }

    public function testRouteRequestCleansUpTheBufferWhenRenderLayoutThrows(): void
    {
        $headers = new RecordingHeaderEmitter();

        $frontController = (new FrontControllerBuilder())->withRequestParametersProvider(new FixedRequestParametersProvider(['late-boom']))
                                                         ->withRouteResolver(new FixedRouteResolver($this->createLateThrowingController()))
                                                         ->withHeadersSentChecker(new FixedHeadersSentChecker(false))
                                                         ->withHeaderEmitter($headers)->build();

        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '500.php', <<<'PHP'
<?php
echo '500 view:' . ($exception instanceof Throwable ? $exception->getMessage() : 'none');
PHP
        );

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('500 view:late-boom', (string)$output);
        self::assertSame([['HTTP/1.1 500 Internal Server Error', true, 500],
                          ['Status: 500 Internal Server Error', true, null],], $headers->headers);
        self::assertSame(500, $_SERVER['REDIRECT_STATUS']);
    }

    public function testRouteRequestFallsBackWhenThe500ViewIsMissingAndHeadersWereSent(): void
    {
        $headers = new RecordingHeaderEmitter();

        $frontController = (new FrontControllerBuilder())->withRequestParametersProvider(new FixedRequestParametersProvider(['boom']))
                                                         ->withRouteResolver(new FixedRouteResolver($this->createThrowingController()))
                                                         ->withHeadersSentChecker(new FixedHeadersSentChecker(true))
                                                         ->withHeaderEmitter($headers)->build();

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertSame('An internal error occurred. Please try again later.', trim((string)$output));
        self::assertSame([], $headers->headers);
        self::assertSame(500, $_SERVER['REDIRECT_STATUS']);
    }

    public function testRouteRequestUsesTheDefaultHeaderCollaboratorsFor404Responses(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/missing-route';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '404.php', <<<'PHP'
<?php
echo '404 view:' . ($_SERVER['REDIRECT_STATUS'] ?? '');
PHP
        );

        ob_start();
        (new FrontController())->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('404 view:404', (string)$output);
        self::assertSame(404, $_SERVER['REDIRECT_STATUS']);
    }

    private function writeDefaultLayout(): void
    {
        file_put_contents($this->layoutsDir . DIRECTORY_SEPARATOR . 'default.php', <<<'PHP'
<?php
echo 'layout:' . $this->getPageTitle() . '|params:' .
    implode(',', $this->getRequestParameters());
PHP
        );
    }

    private function createThrowingController(): Controller
    {
        return new class extends Controller {
            public function execute(): void
            {
                throw new RuntimeException('boom');
            }
        };
    }

    private function createLateThrowingController(): Controller
    {
        return new class extends Controller {
            public function execute(): void
            {
            }

            public function renderLayout(): void
            {
                throw new RuntimeException('late-boom');
            }
        };
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-front-controller-' . uniqid('', true);
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

    private function backupServerVariables(): void
    {
        $this->serverBackup = $_SERVER;
    }

    private function restoreServerVariables(array $server): void
    {
        $_SERVER = $server;
    }
}
