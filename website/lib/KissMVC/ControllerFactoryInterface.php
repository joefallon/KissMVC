<?php
namespace KissMVC;

/**
 * All Controller factories must implement this interface to ensure they can
 * be created by the routeToController function.
 *
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2015 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
interface ControllerFactoryInterface
{
    /**
     * @return Controller
     */
    public static function create();
}
