<?php
namespace Application\Controllers;
use KissMVC\ControllerFactoryInterface;

class PageWithParametersControllerFactory implements ControllerFactoryInterface
{
    /**
     * @return PageWithParametersController
     */
    public static function create()
    {
        return new PageWithParametersController();
    }
}
