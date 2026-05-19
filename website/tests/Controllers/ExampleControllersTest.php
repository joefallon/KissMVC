<?php
declare(strict_types=1);

namespace Tests\Controllers;

use Controllers\IndexController;
use Controllers\PageWithParametersController;
use KissMVC\Application;
use PHPUnit\Framework\TestCase;
use FilesystemIterator;
use ReflectionProperty;

final class ExampleControllersTest extends TestCase
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

        Application::setRegistryItem('layouts_directory', $this->layoutsDir);
        Application::setRegistryItem('views_directory', $this->viewsDir);
    }

    protected function tearDown(): void
    {
        self::resetRegistry();
        $this->removeTempDirectory($this->tempDir);
    }

    public function testIndexControllerConfiguresAndUsesItsDefaults(): void
    {
        file_put_contents($this->layoutsDir . DIRECTORY_SEPARATOR . 'default.php',
            "<?php\necho 'layout:' . \$this->getPageTitle();\n");
        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . 'index.php',
            "<?php\necho 'view:' . \$this->getMessage();\n");

        $controller = new IndexController();
        $controller->setRequestParameters(['abc', '123']);

        self::assertSame('Index', $controller->getPageTitle());
        self::assertSame('Hello, World!', $controller->getMessage());
        self::assertSame('abc', $controller->getRequestParameter());
        self::assertSame('123', $controller->getRequestParameter(1));
        self::assertNull($controller->getRequestParameter(2));

        $controller->execute();

        ob_start();
        $controller->renderLayout();
        $layoutOutput = ob_get_clean();

        ob_start();
        $controller->renderView();
        $viewOutput = ob_get_clean();

        self::assertStringContainsString('layout:Index', (string)$layoutOutput);
        self::assertStringContainsString('view:Hello, World!', (string)$viewOutput);
    }

    public function testPageWithParametersControllerKeepsRequestParametersAvailable(): void
    {
        file_put_contents($this->layoutsDir . DIRECTORY_SEPARATOR . 'default.php',
            "<?php\necho 'layout:' . implode(',', \$this->getRequestParameters());\n");
        file_put_contents($this->viewsDir . DIRECTORY_SEPARATOR . 'page-with-parameters.php',
            "<?php\necho 'view:' . implode(',', \$this->getRequestParameters());\n");

        $controller = new PageWithParametersController();
        $controller->setRequestParameters(['abc', '123', 'xyz']);

        self::assertSame('Page with Parameters', $controller->getPageTitle());
        self::assertSame('abc', $controller->getRequestParameter());
        self::assertSame('123', $controller->getRequestParameter(1));
        self::assertSame('xyz', $controller->getRequestParameter(2));
        self::assertNull($controller->getRequestParameter(3));

        $controller->execute();

        ob_start();
        $controller->renderLayout();
        $layoutOutput = ob_get_clean();

        ob_start();
        $controller->renderView();
        $viewOutput = ob_get_clean();

        self::assertStringContainsString('layout:abc,123,xyz', (string)$layoutOutput);
        self::assertStringContainsString('view:abc,123,xyz', (string)$viewOutput);
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-example-controllers-' . uniqid('', true);
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
