<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use FilesystemIterator;
use KissMVC\Application;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ConfigurationAcceptanceTest extends TestCase
{
    private string $tempDir = '';
    private array $environmentBackup = [];

    protected function setUp(): void
    {
        self::resetRegistry();
        $this->environmentBackup = $this->backupEnvironment([
            'APPLICATION_ENV',
            'SECRET_KEY',
            'SSL_REQUIRED',
            'APP_TIMEZONE',
        ]);
        $this->tempDir = $this->createTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment($this->environmentBackup);
        self::resetRegistry();
        $this->removeTempDirectory($this->tempDir);
    }

    /** KMVC-004-S001 */
    public function testKMVC004S001ConfigurationFileCanReturnAConfigurationArray(): void
    {
        $configFile = $this->writeConfigFile(<<<'PHP'
<?php
return ['alpha' => 'one', 'shared' => 'first'];
PHP
        );

        Application::loadConfiguration($configFile);

        self::assertSame('one', Application::getRegistryItem('alpha'));
        self::assertSame('first', Application::getRegistryItem('shared'));
    }

    /** KMVC-004-S002 */
    public function testKMVC004S002ConfigurationFileCanAssignAConfigArray(): void
    {
        $configFile = $this->writeConfigFile(<<<'PHP'
<?php
$config = ['beta' => 'two', 'shared' => 'second'];
PHP
        );

        Application::loadConfiguration($configFile);

        self::assertSame('two', Application::getRegistryItem('beta'));
        self::assertSame('second', Application::getRegistryItem('shared'));
    }

    /** KMVC-004-S003 */
    public function testKMVC004S003LaterConfigurationLoadsOverrideEarlierValues(): void
    {
        $firstConfig = $this->writeConfigFile(<<<'PHP'
<?php
return ['alpha' => 'one', 'shared' => 'first'];
PHP
        );
        $secondConfig = $this->writeConfigFile(<<<'PHP'
<?php
$config = ['beta' => 'two', 'shared' => 'second'];
PHP
        );

        Application::loadConfiguration($firstConfig);
        Application::loadConfiguration($secondConfig);

        self::assertSame('one', Application::getRegistryItem('alpha'));
        self::assertSame('two', Application::getRegistryItem('beta'));
        self::assertSame('second', Application::getRegistryItem('shared'));
    }

    /** KMVC-004-S004 */
    public function testKMVC004S004MissingRegistryValuesReturnNoValue(): void
    {
        self::assertNull(Application::getRegistryItem('missing'));
    }

    /** KMVC-004-S005 */
    public function testKMVC004S005EnvironmentSpecificConfigurationSelectsDevelopmentDefaults(): void
    {
        $this->setEnvironment([
            'APPLICATION_ENV' => 'development',
            'SECRET_KEY' => false,
            'SSL_REQUIRED' => false,
            'APP_TIMEZONE' => false,
        ]);

        $this->loadMainConfig();

        self::assertSame('development', Application::getRegistryItem('environment'));
        self::assertSame([
            'name' => 'dev-db-name',
            'host' => 'dev-db-host',
            'user' => 'dev-db-username',
            'pass' => 'dev-db-password',
        ], Application::getRegistryItem('db'));
        self::assertSame('place-your-super-secret-key-here', Application::getRegistryItem('secret_key'));
        self::assertFalse(Application::getRegistryItem('ssl_required'));
        self::assertSame('UTC', Application::getRegistryItem('timezone'));
    }

    /** KMVC-004-S006 */
    public function testKMVC004S006EnvironmentSpecificConfigurationSelectsProductionDefaults(): void
    {
        $this->setEnvironment([
            'APPLICATION_ENV' => 'production',
            'SECRET_KEY' => false,
            'SSL_REQUIRED' => false,
            'APP_TIMEZONE' => false,
        ]);

        $this->loadMainConfig();

        self::assertSame('production', Application::getRegistryItem('environment'));
        self::assertSame([
            'name' => 'db-name',
            'host' => 'db-host',
            'user' => 'db-username',
            'pass' => 'db-password',
        ], Application::getRegistryItem('db'));
        self::assertSame('place-your-super-secret-key-here', Application::getRegistryItem('secret_key'));
        self::assertTrue(Application::getRegistryItem('ssl_required'));
        self::assertSame('UTC', Application::getRegistryItem('timezone'));
    }

    /** KMVC-004-S007 */
    public function testKMVC004S007EnvironmentVariablesProvideDeploymentSpecificValues(): void
    {
        $this->setEnvironment([
            'APPLICATION_ENV' => 'production',
            'SECRET_KEY' => 'deployment-secret',
            'SSL_REQUIRED' => 'false',
            'APP_TIMEZONE' => 'America/Los_Angeles',
        ]);

        $this->loadMainConfig();

        self::assertSame('deployment-secret', Application::getRegistryItem('secret_key'));
        self::assertFalse(Application::getRegistryItem('ssl_required'));
        self::assertSame('America/Los_Angeles', Application::getRegistryItem('timezone'));
    }

    private function loadMainConfig(): void
    {
        Application::loadConfiguration(BASE_PATH . '/src/Config/main.php');
    }

    private function writeConfigFile(string $contents): string
    {
        $filePath = $this->tempDir . DIRECTORY_SEPARATOR . 'config-' . uniqid('', true) . '.php';
        file_put_contents($filePath, $contents);

        return $filePath;
    }

    private function createTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kissmvc-configuration-' . uniqid('', true);
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
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function backupEnvironment(array $names): array
    {
        $backup = [];

        foreach($names as $name)
        {
            $value = getenv($name);
            $backup[$name] = $value === false ? false : $value;
        }

        return $backup;
    }

    private function setEnvironment(array $values): void
    {
        foreach($values as $name => $value)
        {
            if($value === false)
            {
                putenv($name);
                continue;
            }

            putenv($name . '=' . $value);
        }
    }

    private function restoreEnvironment(array $values): void
    {
        foreach($values as $name => $value)
        {
            if($value === false)
            {
                putenv($name);
                continue;
            }

            putenv($name . '=' . $value);
        }
    }
}
