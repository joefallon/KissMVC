<?php declare(strict_types=1);

namespace KissMVC;

final class FunctionRouteResolver implements RouteResolverInterface
{
    public function resolveRoute(string $route): ?Controller
    {
        return function_exists('routeToController') ? routeToController($route) : null;
    }
}
