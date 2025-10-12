<?php
declare(strict_types=1);

namespace Application\Controllers;

use KissMVC\Controller;
use KissMVC\ControllerFactoryInterface;

class IndexControllerFactory implements ControllerFactoryInterface
{
    /**
     * @return IndexController
     */
    public static function create(): Controller
    {
        return new IndexController();
    }
}
