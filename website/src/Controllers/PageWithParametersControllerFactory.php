<?php
declare(strict_types=1);

namespace Controllers;

use KissMVC\Controller;
use KissMVC\ControllerFactoryInterface;

class PageWithParametersControllerFactory implements ControllerFactoryInterface
{
    /**
     * @return PageWithParametersController
     */
    public static function create(): Controller
    {
        return new PageWithParametersController();
    }
}
