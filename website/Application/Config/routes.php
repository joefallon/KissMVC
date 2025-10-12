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
 * @param string $route The single URL segment to route (e.g. 'default')
 *
 * @return Controller|null Controller instance when matched, or null when no
 *                         route exists (FrontController will show 404).
 */
function routeToController(string $route): ?Controller
{
    // Normalize the incoming route string.
    $route =  strtolower(trim($route));

    // Simple default behaviour: empty route maps to 'default'.
    if($route === '')
    {
        // The default route must always exist in the $routes map below. In other frameworks
        // this might be called 'home' or 'index'.
        $route = 'default';
    }

    switch($route)
    {
        case 'default':
            return IndexControllerFactory::create();
        case 'page-with-parameters':
            return PageWithParametersControllerFactory::create();
//        case 'view-items':
//            return ViewItemsControllerFactory::create();
        default:
            return null;
    }
}
