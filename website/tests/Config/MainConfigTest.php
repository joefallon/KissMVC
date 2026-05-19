<?php
declare(strict_types=1);

namespace Tests\Config;

use KissMVC\Application;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class MainConfigTest extends TestCase
{
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
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment($this->environmentBackup);
        self::resetRegistry();
    }

    public function testLoadsDevelopmentConfigFromEnvironmentVariables(): void
    {
        $this->setEnvironment([
            'APPLICATION_ENV' => 'development',
            'SECRET_KEY' => 'development-secret',
            'SSL_REQUIRED' => 'true',
            'APP_TIMEZONE' => 'America/Los_Angeles',
        ]);

        $this->loadMainConfig();

        self::assertSame('development', Application::getRegistryItem('environment'));
        self::assertSame([
            'name' => 'dev-db-name',
            'host' => 'dev-db-host',
            'user' => 'dev-db-username',
            'pass' => 'dev-db-password',
        ], Application::getRegistryItem('db'));
        self::assertSame('development-secret', Application::getRegistryItem('secret_key'));
        self::assertTrue(Application::getRegistryItem('ssl_required'));
        self::assertSame('America/Los_Angeles', Application::getRegistryItem('timezone'));
        self::assertSame(BASE_PATH . '/src/Views', Application::getRegistryItem('views_directory'));
        self::assertSame(BASE_PATH . '/src/Partials', Application::getRegistryItem('partials_directory'));
        self::assertSame(BASE_PATH . '/src/Layouts', Application::getRegistryItem('layouts_directory'));
    }

    public function testLoadsProductionConfigWithDefaultFallbacks(): void
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

    private function loadMainConfig(): void
    {
        Application::loadConfiguration(BASE_PATH . '/src/Config/main.php');
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

    private static function resetRegistry(): void
    {
        $property = new ReflectionProperty(Application::class, 'config');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
