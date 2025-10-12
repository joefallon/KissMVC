<?php
declare(strict_types=1);

use KissMVC\Controller;
use Application\Controllers\IndexControllerFactory;
use Application\Controllers\PageWithParametersControllerFactory;

/**
 * routeToController
 *
 * Simple, explicit router that maps a single URL segment (one level deep)
 * to a Controller instance. Keep this file small and easy to edit: add new
 * routes by adding entries to the $routes map below.
 *
 * Design goals (KISS + Clean Code):
 *  - Small function doing one job: map a route string to a Controller.
 *  - Clear naming: route keys should be descriptive and match URL segments.
 *  - Explicit factories: each route points to a factory callable that
 *    returns a concrete Controller instance.
 *
 * Conventions and tips for contributors:
 *  - Routes are one segment only. For e.g. '/admin-dashboard' the segment is
 *    'admin-dashboard'. Avoid embedding slashes in the route key.
 *  - Factory classes should provide a public static create() method that
 *    returns a Controller. This keeps controller construction consistent.
 *  - Prefer adding an explicit entry to the $routes map instead of relying
 *    on convention-based magic. Explicit is easier to read and maintain.
 *  - If a route requires custom construction logic, provide a closure as the
 *    map value (see example below).
 *
 * Example: add a new route 'about-us' that uses AboutControllerFactory:
 *  $routes['about-us'] = [AboutControllerFactory::class, 'create'];
 *
 * @param string $route The single URL segment to route (e.g. 'default')
 *
 * @return Controller|null Controller instance when matched, or null when no
 *                         route exists (FrontController will show 404).
 */
function routeToController(string $route): ?Controller
{
    // Normalize the incoming route string.
    $route = trim($route);

    // Simple default behaviour: empty route maps to 'default'. This matches the
    // historical behavior where the FrontController used 'default' when no
    // segments were present.
    if($route === '')
    {
        // The default route must always exist in the $routes map below. In other frameworks
        // this might be called 'home' or 'index'.
        $route = 'default';
    }

    // Define the explicit route -> factory map. Keep this map the single
    // source-of-truth for routes so adding/removing a route is trivial.
    static $routes = [
        // Keep one entry per line for clarity. Factories may be class
        // method callables or closures.
        'default'              => [IndexControllerFactory::class, 'create'],
        'page-with-parameters' => [PageWithParametersControllerFactory::class, 'create'],

        // Example of a closure-based route for custom construction:
        // 'custom' => function (): Controller { return CustomFactory::create(); },
    ];


    // If no route exists, return null -> FrontController will handle 404.
    if(!isset($routes[$route]))
    {
        return null;
    }

    $factory = $routes[$route];

    // Allow either a callable (closure or [Class, 'method']) or an invokable object.
    // Call it and validate the returned value.
    if(is_callable($factory))
    {
        // For static class method calls, the array format [ClassName::class, 'methodName']
        // is used, which PHP interprets as a callable to ClassName::methodName().
        // This enables explicit mapping of routes to static factory methods.
        // See the $routes map above for examples.
        $controller = call_user_func($factory);

        if($controller instanceof Controller)
        {
            // An instance of Controller (or subclass) was created: return it.
            return $controller;
        }

        // If the factory returned something unexpected, warn and treat as
        // not found to avoid fatal errors during request handling.
        trigger_error(sprintf('Route factory for "%s" did not return a Controller', $route), E_USER_WARNING);

        return null;
    }

    // Misconfigured map entry: warn and return null.
    trigger_error(sprintf('Invalid route factory configured for "%s"', $route), E_USER_WARNING);

    return null;
}
