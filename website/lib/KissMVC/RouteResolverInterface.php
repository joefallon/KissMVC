<?php declare(strict_types=1);

namespace KissMVC;

interface RouteResolverInterface
{
    public function resolveRoute(string $route): ?Controller;
}
