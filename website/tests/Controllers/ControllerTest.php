<?php
declare(strict_types=1);

namespace Tests\Controllers;

use FilesystemIterator;
use KissMVC\Application;
use KissMVC\Controller;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ControllerTest extends TestCase
{
    private string $tempDir = '';
    private string $layoutsDir = '';
    private string $viewsDir = '';
    private string $partialsDir = '';

    protected function setUp(): void
    {
        self::resetRegistry();

        $this->tempDir = $this->createTempDirectory();
        $this->layoutsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'layouts';
        $this->viewsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'views';
        $this->partialsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'partials';

        mkdir($this->layoutsDir, 0777, true);
        mkdir($this->viewsDir, 0777, true);
        mkdir($this->partialsDir, 0777, true);

        Application::setRegistryItem('layouts_directory', $this->layoutsDir);
        Application::setRegistryItem('views_directory', $this->viewsDir);
        Application::setRegistryItem('partials_directory', $this->partialsDir);
    }

    protected function tearDown(): void
    {
        self::resetRegistry();
        $this->removeTempDirectory($this->tempDir);
    }

    public function testControllerAccessorsReturnConfiguredValues(): void
    {
        $controller = new TestController();
        $controller->configure(
            'Test Page',
            'layout.php',
            'view.php',
            ['styles.css', 'print.css'],
            ['app.js']
        );
        $controller->setRequestParameters(['abc', '123', 'xyz']);

        self::assertSame('Test Page', $controller->getPageTitle());
        self::assertSame(['styles.css', 'print.css'], $controller->getCssFiles());
        self::assertSame(['app.js'], $controller->getJavaScriptFiles());
        self::assertSame(['abc', '123', 'xyz'], $controller->getRequestParameters());
    }

    public function testDefaultControllerStateIsEmpty(): void
    {
        $controller = new TestController();

        self::assertNull($controller->getPageTitle());
        self::assertSame([], $controller->getCssFiles());
        self::assertSame([], $controller->getJavaScriptFiles());
        self::assertSame([], $controller->getRequestParameters());
        self::assertNull($controller->getVersion());
    }

    public function testExecuteDefaultsToANoOp(): void
    {
        $controller = new TestController();

        $controller->execute();

        self::assertTrue(true);
    }

    public function testGetVersionReturnsConfiguredVersion(): void
    {
        Application::setRegistryItem('version', '1.2.3');

        $controller = new TestController();

        self::assertSame('1.2.3', $controller->getVersion());
    }

    public function testGetVersionReturnsNullForNonStringValues(): void
    {
        Application::setRegistryItem('version', ['not' => 'a string']);

        $controller = new TestController();

        self::assertNull($controller->getVersion());
    }

    public function testRenderLayoutAndViewIncludeTemporaryFixtureFiles(): void
    {
        file_put_contents(
            $this->layoutsDir . DIRECTORY_SEPARATOR . 'layout.php',
            "<?php\necho 'layout:' . \$this->getPageTitle();\n"
        );
        file_put_contents(
            $this->viewsDir . DIRECTORY_SEPARATOR . 'view.php',
            "<?php\necho 'view:' . implode(',', \$this->getRequestParameters());\n"
        );

        $controller = new TestController();
        $controller->configure('Rendered Title', 'layout.php', 'view.php');
        $controller->setRequestParameters(['first', 'second']);

        ob_start();
        $controller->renderLayout();
        $layoutOutput = ob_get_clean();

        ob_start();
        $controller->renderView();
        $viewOutput = ob_get_clean();

        self::assertStringContainsString('layout:Rendered Title', (string)$layoutOutput);
        self::assertStringContainsString('view:first,second', (string)$viewOutput);
    }

    public function testRenderPartialUsesConfiguredPartialsDirectory(): void
    {
        file_put_contents(
            $this->partialsDir . DIRECTORY_SEPARATOR . 'snippet.php',
            "<?php\necho 'partial:' . (\$data['value'] ?? '');\n"
        );

        $controller = new TestController();

        ob_start();
        $controller->renderPartial('snippet.php', ['value' => 'fixture']);
        $output = ob_get_clean();

        self::assertStringContainsString('partial:fixture', (string)$output);
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-controller-' . uniqid('', true);
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
}

final class TestController extends Controller
{
    public function configure(
        string $pageTitle,
        string $layoutFile,
        string $viewFile,
        array $cssFiles = [],
        array $jsFiles = []
    ): void {
        $this->setPageTitle($pageTitle);
        $this->setLayout($layoutFile);
        $this->setView($viewFile);

        foreach($cssFiles as $cssFile)
        {
            $this->addCssFile($cssFile);
        }

        foreach($jsFiles as $jsFile)
        {
            $this->addJavaScriptFile($jsFile);
        }
    }

    /** @noinspection PhpRedundantMethodOverrideInspection */
    public function setRequestParameters(array $requestParameters): void
    {
        parent::setRequestParameters($requestParameters);
    }
}
