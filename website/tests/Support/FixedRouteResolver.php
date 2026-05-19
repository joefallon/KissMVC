<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\Controller;
use KissMVC\RouteResolverInterface;

final class FixedRouteResolver implements RouteResolverInterface
{
    public function __construct(private ?Controller $controller)
    {
    }

    public function resolveRoute(string $route): ?Controller
    {
        return $this->controller;
    }
}
