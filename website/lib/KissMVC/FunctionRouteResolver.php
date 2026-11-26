<?php declare(strict_types=1);

namespace KissMVC;

final class FunctionRouteResolver implements RouteResolverInterface
{
    public function resolveRoute(string $route): ?Controller
    {
        if(!function_exists('routeToController'))
        {
            return null;
        }

        $controller = routeToController($route);

        return $controller;
    }
}
