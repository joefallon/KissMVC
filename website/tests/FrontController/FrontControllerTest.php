<?php
declare(strict_types=1);

namespace Tests\FrontController;

use Controllers\IndexController;
use Controllers\PageWithParametersController;
use FilesystemIterator;
use KissMVC\Application;
use KissMVC\Controller;
use KissMVC\FrontController;
use KissMVC\FrontControllerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class FrontControllerTest extends TestCase
{
    private string $tempDir = '';
    private string $layoutsDir = '';
    private string $viewsDir = '';

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        self::resetRegistry();

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
        $this->restoreServerVariables($this->serverBackup ?? []);
        $this->removeTempDirectory($this->tempDir);
    }

    public function testGetRequestParametersReturnsNullForRootRequest(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $frontController = new FrontControllerProbe();

        self::assertNull($frontController->readRequestParameters());
    }

    public function testGetRequestParametersStripsScriptDirectoryAndQueryString(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/subdir/page-with-parameters/abc/123/xyz?foo=bar';
        $_SERVER['SCRIPT_NAME'] = '/subdir/index.php';

        $frontController = new FrontControllerProbe();

        self::assertSame(['page-with-parameters', 'abc', '123', 'xyz'], $frontController->readRequestParameters());
    }

    public function testGetRequestParametersSkipsEmptySegments(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/subdir/page-with-parameters//abc/123/xyz/?foo=bar';
        $_SERVER['SCRIPT_NAME'] = '/subdir/index.php';

        $frontController = new FrontControllerProbe();

        self::assertSame(
            ['page-with-parameters', 'abc', '123', 'xyz'],
            $frontController->readRequestParameters()
        );
    }

    public function testResolveControllerReturnsExpectedControllers(): void
    {
        $frontController = new FrontControllerProbe();

        self::assertInstanceOf(IndexController::class, $frontController->readResolvedController('default'));
        self::assertInstanceOf(PageWithParametersController::class,
            $frontController->readResolvedController('page-with-parameters'));
        self::assertNull($frontController->readResolvedController('missing-route'));
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
        $_SERVER['REQUEST_URI'] = '/page-with-parameters/abc/123/xyz?foo=bar';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->writeDefaultLayout();

        ob_start();
        (new FrontController())->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('layout:Page with Parameters|params:abc,123,xyz', (string)$output);
    }

    public function testRouteRequestDisplaysThe404ViewForUnknownRoutes(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/missing-route';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '404.php',
            "<?php\necho '404 view:' . (\$_SERVER['REDIRECT_STATUS'] ?? '');\n");

        ob_start();
        new FrontController()->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('404 view:404', (string)$output);
        self::assertSame(404, $_SERVER['REDIRECT_STATUS']);
    }

    public function testDisplay404PageFallsBackWhenTheViewIsMissing(): void
    {
        $frontController = new FrontControllerProbe();

        ob_start();
        $frontController->render404();
        $output = ob_get_clean();

        self::assertSame('404 Not Found', trim((string)$output));
    }

    public function testRouteRequestDisplaysThe500ViewForControllerExceptions(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/boom';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $str = <<<'PHP'
<?php
$message = isset($exception) && $exception instanceof Throwable ? $exception->getMessage() : 'none';
echo '500 view:' . $message;
PHP;
        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '500.php', $str);

        $frontController = new ControlledFrontController(null, new ThrowingController('boom'));

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('500 view:boom', (string)$output);
        self::assertSame(500, $_SERVER['REDIRECT_STATUS']);
    }

    public function testRouteRequestCleansUpTheBufferWhenRenderLayoutThrows(): void
    {
        $this->backupServerVariables();
        $_SERVER['REQUEST_URI'] = '/late-boom';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $str = <<<'PHP'
<?php
echo '500 view:' . ($exception instanceof Throwable ? $exception->getMessage() : 'none');
PHP;
        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . '500.php', $str);

        $frontController = new ControlledFrontController(null, new LateThrowingController('late-boom'));

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertStringContainsString('500 view:late-boom', (string)$output);
        self::assertSame(500, $_SERVER['REDIRECT_STATUS']);
    }

    public function testDisplay500PageFallsBackWhenTheViewIsMissing(): void
    {
        $frontController = new FrontControllerProbe();

        ob_start();
        $frontController->render500(new RuntimeException('boom'));
        $output = ob_get_clean();

        self::assertSame('An internal error occurred. Please try again later.', trim((string)$output));
    }

    public function testRouteRequestBuiltWithInjectedDependenciesSkips404HeaderEmissionWhenHeadersWereSent(): void
    {
        $headers = [];

        $frontController = (new FrontControllerBuilder())
            ->withRequestParametersProvider(static fn (): ?array => ['missing-route'])
            ->withRouteResolver(static fn (string $route): ?Controller => null)
            ->withHeadersSentChecker(static fn (): bool => true)
            ->withHeaderEmitter(static function (
                string $header,
                bool $replace = true,
                ?int $responseCode = null
            ) use (&$headers): void {
                $headers[] = [$header, $replace, $responseCode];
            })
            ->build();

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertSame('404 Not Found', trim((string)$output));
        self::assertSame([], $headers);
    }

    public function testRouteRequestBuiltWithInjectedDependenciesEmits500HeadersForExceptions(): void
    {
        $headers = [];

        $frontController = (new FrontControllerBuilder())
            ->withRequestParametersProvider(static fn (): ?array => ['boom'])
            ->withRouteResolver(static fn (string $route): ?Controller => new ThrowingController('ignored.php'))
            ->withHeadersSentChecker(static fn (): bool => false)
            ->withHeaderEmitter(static function (
                string $header,
                bool $replace = true,
                ?int $responseCode = null
            ) use (&$headers): void {
                $headers[] = [$header, $replace, $responseCode];
            })
            ->build();

        ob_start();
        $frontController->routeRequest();
        $output = ob_get_clean();

        self::assertSame('An internal error occurred. Please try again later.', trim((string)$output));
        self::assertSame([
            ['HTTP/1.1 500 Internal Server Error', true, 500],
            ['Status: 500 Internal Server Error', true, null],
        ], $headers);
    }

    private function writeDefaultLayout(): void
    {
        $str = "<?php\necho 'layout:' . \$this->getPageTitle() . '|params:' . 
               implode(',', \$this->getRequestParameters());\n";
        file_put_contents($this->layoutsDir . DIRECTORY_SEPARATOR . 'default.php', $str);
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
        if($server !== [])
        {
            $_SERVER = $server;
        }
    }

    private array $serverBackup = [];
}

final class FrontControllerProbe extends FrontController
{
    public function readRequestParameters(): ?array
    {
        return parent::getRequestParameters();
    }

    public function readResolvedController(string $route): ?Controller
    {
        return parent::resolveController($route);
    }

    public function render404(): void
    {
        parent::display404Page();
    }

    public function render500(?Throwable $e = null): void
    {
        parent::display500Page($e);
    }
}

final class ControlledFrontController extends FrontController
{
    public function __construct(private ?array $requestParameters, private Controller $controller)
    {
    }

    protected function getRequestParameters(): ?array
    {
        return $this->requestParameters;
    }

    protected function resolveController(string $route): ?Controller
    {
        return $this->controller;
    }
}

final class ThrowingController extends Controller
{
    public function __construct(string $layoutFile)
    {
        parent::__construct();
        $this->setLayout($layoutFile);
    }

    public function execute(): void
    {
        throw new RuntimeException('boom');
    }
}

final class LateThrowingController extends Controller
{
    public function __construct(string $layoutFile)
    {
        parent::__construct();
        $this->setLayout($layoutFile);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(): void
    {
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function renderLayout(): void
    {
        throw new RuntimeException('late-boom');
    }
}
