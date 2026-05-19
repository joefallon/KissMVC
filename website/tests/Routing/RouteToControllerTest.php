<?php
declare(strict_types=1);

namespace Tests\Routing;

use Controllers\IndexController;
use Controllers\PageWithParametersController;
use PHPUnit\Framework\TestCase;

final class RouteToControllerTest extends TestCase
{
    public function testEmptyRouteResolvesTheDefaultController(): void
    {
        $controller = \routeToController('');

        self::assertInstanceOf(IndexController::class, $controller);
    }

    public function testDefaultRouteResolvesTheIndexController(): void
    {
        $controller = \routeToController('default');

        self::assertInstanceOf(IndexController::class, $controller);
    }

    public function testPageWithParametersRouteResolvesTheParametersController(): void
    {
        $controller = \routeToController('page-with-parameters');

        self::assertInstanceOf(PageWithParametersController::class, $controller);
    }

    public function testRouteMatchingIsNormalizedForCasingAndTrim(): void
    {
        $controller = \routeToController('  PAGE-WITH-PARAMETERS  ');

        self::assertInstanceOf(PageWithParametersController::class, $controller);
    }

    public function testUnknownRoutesReturnNull(): void
    {
        self::assertNull(\routeToController('missing-route'));
    }
}
