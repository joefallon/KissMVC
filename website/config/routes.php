<?php
/**
 * @param string $route
 *
 * @return \KissMVC\Controller
 */
function routeToController($route)
{
    switch($route)
    {
        case 'default':
            return IndexControllerBuilder::create();
        case 'page-with-parameters':
            return PageWithParametersControllerBuilder::create();
        default:
            return null;
    }
}
