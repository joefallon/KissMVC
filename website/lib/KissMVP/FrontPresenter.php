<?php
namespace KissMVP;

require_once(LIB_PATH . '/KissMVP/Application.php');
require_once(LIB_PATH . '/KissMVP/Controller.php');

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   KissMVP
 */
class FrontController
{
    const DEFAULT_PRESENTER = 'default';
    const HTTP_404_FILENAME = '404.php';


    /**
     * This method routes the request to the correct controller, provides the
     * controller with the request parameters, executes the controller, and then
     * tells the controller to render the page.
     */
    public function routeRequest()
    {
        $requestParameters = $this->getRequestParameters();
        $controller = null;

        if($requestParameters == null)
        {
            // No parameters were supplied. Therefore, route the the default controller.
            $controller = routeToController(self::DEFAULT_PRESENTER);
        }
        else
        {
            $pageName  = $requestParameters[0];
            $controller = routeToController($pageName);

            // Remove non-parameters (i.e. the page name) from the array.
            unset($requestParameters[0]);
            $requestParameters = array_values($requestParameters);
        }

        if($controller == null)
        {
            // A page was specified. However its controller does not exist.
            // Therefore, redirect to the 404 page.
            $_SERVER['REDIRECT_STATUS'] = 404;
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            header("Status: 404 Not Found");
            $dir  = Application::getRegistryItem('views_directory');
            $view = $dir . '/' . self::HTTP_404_FILENAME;
            require($view);

            die();
        }

        $controller->setRequestParameters($requestParameters);
        $controller->execute();
        $controller->renderLayout();
    }


    /**
     * @return array
     */
    private function getRequestParameters()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir  = dirname($scriptName);

        if($scriptDir != '/')
        {
            $request = str_replace($scriptDir, '', $requestUri);
        }
        else
        {
            $request = $requestUri;
        }

        $request = trim($request, '/');

        // Convert the segments to an array.
        if(strlen($request) > 0)
        {
            $requestParams = explode('/', $request);

            return $requestParams;
        }
        else
        {
            return null;
        }
    }
}
