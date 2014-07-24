<?php
use KissMVC\ControllerBuilderInterface;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class PageWithParametersControllerBuilder implements ControllerBuilderInterface
{
    public static function create()
    {
        return new PageWithParametersController();
    }
}
