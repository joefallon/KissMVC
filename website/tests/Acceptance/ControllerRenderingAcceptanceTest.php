<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use KissMVC\Application;
use KissMVC\Controller;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ControllerRenderingAcceptanceTest extends TestCase
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

        $this->writeFixtures();

        Application::setRegistryItem('layouts_directory', $this->layoutsDir);
        Application::setRegistryItem('views_directory', $this->viewsDir);
        Application::setRegistryItem('partials_directory', $this->partialsDir);
    }

    protected function tearDown(): void
    {
        self::resetRegistry();
        $this->removeTempDirectory($this->tempDir);
    }

    /** KMVC-003-S001 */
    public function testKMVC003S001ControllerCanRenderConfiguredLayout(): void
    {
        $controller = $this->createController('render-layout.php');

        $output = $this->renderController(static function (Controller $controller): void {
            $controller->renderLayout();
        }, $controller);

        self::assertStringContainsString('layout-start', $output);
    }

    /** KMVC-003-S002 */
    public function testKMVC003S002LayoutCanRenderSelectedView(): void
    {
        $controller = $this->createController('render-view.php', 'selected-view.php');

        $output = $this->renderController(static function (Controller $controller): void {
            $controller->renderLayout();
        }, $controller);

        self::assertStringContainsString('layout-start', $output);
        self::assertStringContainsString('view-marker', $output);
    }

    /** KMVC-003-S003 */
    public function testKMVC003S003ViewCanAccessPublicControllerPresentationMethods(): void
    {
        $controller = $this->createController('render-layout.php', 'presentation-view.php');

        $output = $this->renderController(static function (Controller $controller): void {
            $controller->renderView();
        }, $controller);

        self::assertStringContainsString('presentation-value:presentation-value', $output);
    }

    /** KMVC-003-S004 */
    public function testKMVC003S004ViewCanRenderAPartial(): void
    {
        $controller = $this->createController('render-layout.php', 'view-with-partial.php');

        $output = $this->renderController(static function (Controller $controller): void {
            $controller->renderView();
        }, $controller);

        self::assertStringContainsString('view-start', $output);
        self::assertStringContainsString('partial-data:partial-data', $output);
    }

    /** KMVC-003-S005 */
    public function testKMVC003S005PartialReceivesProvidedData(): void
    {
        $controller = $this->createController('render-layout.php');

        $output = $this->renderController(static function (Controller $controller): void {
            $controller->renderPartial('partial.php', ['value' => 'partial-data']);
        }, $controller);

        self::assertStringContainsString('partial-data:partial-data', $output);
    }

    /** KMVC-003-S006 */
    public function testKMVC003S006ControllerCanExposeRequestParametersToAView(): void
    {
        $controller = $this->createController('render-layout.php', 'params-view.php', ['a', 'b', 'c']);

        $output = $this->renderController(static function (Controller $controller): void {
            $controller->renderView();
        }, $controller);

        self::assertStringContainsString('params:a,b,c', $output);
    }

    private function createController(string $layoutFileName, ?string $viewFileName = null,
                                      array $requestParameters = []): Controller
    {
        $controller = new class extends Controller {
            private string $presentationValue = 'presentation-value';

            public function configure(string $layoutFileName, ?string $viewFileName, array $requestParameters): void
            {
                $this->setLayout($layoutFileName);

                if($viewFileName !== null)
                {
                    $this->setView($viewFileName);
                }

                $this->setRequestParameters($requestParameters);
            }

            public function getPresentationValue(): string
            {
                return $this->presentationValue;
            }
        };

        $controller->configure($layoutFileName, $viewFileName, $requestParameters);

        return $controller;
    }

    private function renderController(callable $renderer, Controller $controller): string
    {
        ob_start();
        $renderer($controller);

        return (string)ob_get_clean();
    }

    private function writeFixtures(): void
    {
        $this->writeFile('layouts', 'render-layout.php', <<<'PHP'
<?php
echo 'layout-start';
PHP
        );

        $this->writeFile('layouts', 'render-view.php', <<<'PHP'
<?php
echo 'layout-start|';
$this->renderView();
echo '|layout-end';
PHP
        );

        $this->writeFile('views', 'selected-view.php', <<<'PHP'
<?php
echo 'view-marker';
PHP
        );

        $this->writeFile('views', 'presentation-view.php', <<<'PHP'
<?php
echo 'presentation-value:' . $this->getPresentationValue();
PHP
        );

        $this->writeFile('views', 'view-with-partial.php', <<<'PHP'
<?php
echo 'view-start|';
$this->renderPartial('partial.php', ['value' => 'partial-data']);
echo '|view-end';
PHP
        );

        $this->writeFile('views', 'params-view.php', <<<'PHP'
<?php
echo 'params:' . implode(',', $this->getRequestParameters());
PHP
        );

        $this->writeFile('partials', 'partial.php', <<<'PHP'
<?php
echo 'partial-data:' . ($data['value'] ?? 'missing');
PHP
        );
    }

    private function writeFile(string $directory, string $fileName, string $contents): void
    {
        $targetDirectory = match ($directory)
        {
            'layouts' => $this->layoutsDir,
            'partials' => $this->partialsDir,
            'views' => $this->viewsDir,
            default => throw new \InvalidArgumentException('Unknown fixture directory: ' . $directory),
        };

        file_put_contents($targetDirectory . DIRECTORY_SEPARATOR . $fileName, $contents);
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-controller-rendering-' . uniqid('', true);
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
}
