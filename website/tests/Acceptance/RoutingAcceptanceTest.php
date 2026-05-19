<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Controllers\IndexController;
use Controllers\PageWithParametersController;
use PHPUnit\Framework\TestCase;

final class RoutingAcceptanceTest extends TestCase
{
    /** KMVC-001-S001 */
    public function testKMVC001S001EmptyRouteResolvesToDefaultController(): void
    {
        $controller = \routeToController('');

        self::assertInstanceOf(IndexController::class, $controller);
    }

    /** KMVC-001-S002 */
    public function testKMVC001S002DefaultRouteResolvesToDefaultPageController(): void
    {
        $controller = \routeToController('default');

        self::assertInstanceOf(IndexController::class, $controller);
    }

    /** KMVC-001-S003 */
    public function testKMVC001S003RouteMatchingTrimsWhitespace(): void
    {
        $controller = \routeToController('  default  ');

        self::assertInstanceOf(IndexController::class, $controller);
    }

    /** KMVC-001-S004 */
    public function testKMVC001S004RouteMatchingIsCaseInsensitive(): void
    {
        $controller = \routeToController('  PAGE-WITH-PARAMETERS  ');

        self::assertInstanceOf(PageWithParametersController::class, $controller);
    }

    /** KMVC-001-S005 */
    public function testKMVC001S005UnknownRoutesResolveToNoController(): void
    {
        self::assertNull(\routeToController('missing-route'));
    }

    /** KMVC-001-S006 */
    public function testKMVC001S006PageWithParametersRouteResolvesToItsController(): void
    {
        $controller = \routeToController('page-with-parameters');

        self::assertInstanceOf(PageWithParametersController::class, $controller);
    }
}
