<?php
use KissMVP\ControllerBuilderInterface;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class IndexControllerBuilder implements ControllerBuilderInterface
{
    public static function create()
    {
        return new IndexController();
    }

}
