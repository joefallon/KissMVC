<?php
/**
 * @param string $route
 *
 * @return \KissMVP\Presenter
 */
function routeToPresenter($route)
{
    switch($route)
    {
        case 'default':
            return IndexPresenterFactory::create();
        case 'page-with-parameters':
            return PageWithParametersPresenterFactory::create();
        default:
            return null;
    }
}
