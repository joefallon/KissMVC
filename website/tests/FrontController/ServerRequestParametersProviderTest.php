<?php
declare(strict_types=1);

namespace Tests\FrontController;

use KissMVC\ServerRequestParametersProvider;
use PHPUnit\Framework\TestCase;

final class ServerRequestParametersProviderTest extends TestCase
{
    private array $serverBackup = [];

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testRequestPathSegmentsAreTrimmedFromLeadingAndTrailingSlashes(): void
    {
        $this->setServerVariables([
            'REQUEST_URI' => '/page-with-parameters/abc/',
            'SCRIPT_NAME' => '/index.php',
        ]);

        $requestParameters = new ServerRequestParametersProvider()->getRequestParameters();
        self::assertSame(['page-with-parameters', 'abc'], $requestParameters);
    }

    public function testRootRequestReturnsNull(): void
    {
        $this->setServerVariables([
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
        ]);

        self::assertNull((new ServerRequestParametersProvider())->getRequestParameters());
    }

    public function testQueryOnlyPseudoSegmentDoesNotCreateAnEmptyParameter(): void
    {
        $this->setServerVariables([
            'REQUEST_URI' => '/?ignored=value',
            'SCRIPT_NAME' => '/index.php',
        ]);

        $requestParameters = new ServerRequestParametersProvider()->getRequestParameters();
        self::assertSame([], $requestParameters);
    }

    private function setServerVariables(array $server): void
    {
        foreach($server as $key => $value)
        {
            $_SERVER[$key] = $value;
        }
    }
}
