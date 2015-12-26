<?php
namespace KissMVC;
use Exception;
require_once(CONFIG_PATH . '/routes.php');

class FrontController
{
    const DEFAULT_CONTROLLER = 'default';
    const HTTP_404_VIEW      = '404.php';
    const HTTP_500_VIEW      = '500.php';

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
            // No parameters were supplied. Therefore, route the the default Controller.
            // See 'config/routes.php'.
            $controller = routeToController(self::DEFAULT_CONTROLLER);
        }
        else
        {
            $pageName = $requestParameters[0];

            // See 'config/routes.php'.
            $controller = routeToController($pageName);

            // Remove non-parameters (i.e. the page name) from the array.
            unset($requestParameters[0]);
            $requestParameters = array_values($requestParameters);
        }

        if($controller == null)
        {
            $this->display404Page();
        }

        $controller->setRequestParameters($requestParameters);

        if(APPLICATION_ENV == 'production')
        {
            try
            {
                $controller->execute();
            }
            catch(Exception $ex)
            {
                $this->display500Page($controller);
            }
        }
        else
        {
            $controller->execute();
        }

        $controller->renderLayout();
    }

    /*************************************************************************
     * Private Functions
     *************************************************************************/

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

    /**
     * A page was specified. However its Controller does not exist. Therefore,
     * display the 404 page.
     */
    private function display404Page()
    {
        $_SERVER['REDIRECT_STATUS'] = 404;
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        header('Status: 404 Not Found');
        $dir = Application::getRegistryItem('views_directory');
        $view = $dir . '/' . self::HTTP_404_VIEW;
        require($view);
        die();
    }

    /**
     * An internal server error occurred. Therefore, display the 500 page.
     *
     * @param Controller $controller
     */
    private function display500Page($controller)
    {
        $_SERVER['REDIRECT_STATUS'] = 500;
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
        header('Status: 500 Internal Server Error');
        $controller->error500();
        $dir = Application::getRegistryItem('views_directory');
        $view = $dir . '/' . self::HTTP_500_VIEW;
        require($view);
        die();
    }
}
