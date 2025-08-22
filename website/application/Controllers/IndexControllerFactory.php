<?php
namespace Application\Controllers;
use KissMVC\ControllerFactoryInterface;

class IndexControllerFactory implements ControllerFactoryInterface
{
    /**
     * @return IndexController
     */
    public static function create()
    {
        return new IndexController();
    }
}
