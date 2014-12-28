<?php
/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2015 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
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
