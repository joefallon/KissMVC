<?php
/**
 * @param string $route
 *
 * @return \KissMVC\Controller
 */
function routeToController($route)
{
    /*
     * Place all routes here. Routes are limited to one level deep. For example,
     * for the admin dashboard page, a router would exist for 'admin-dashboard'.
     * The admin dashboard would be accessible at the URL
     * http://www.myapp.com/admin-dashboard. Additionally,
     * the controller for the admin dashboard would be named
     * 'AdminDashboardController'. The factory for the controller would be
     * named 'AdminDashboardControllerFactory'.
     */
    switch($route)
    {
        case 'default':
            return IndexControllerFactory::create();
        case 'page-with-parameters':
            return PageWithParametersControllerFactory::create();
        default:
            return null;
    }
}
