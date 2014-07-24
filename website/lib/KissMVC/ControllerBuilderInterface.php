<?php
namespace KissMVC;

/**
 * All controller factories must implement this interface to ensure they can
 * be created by the routeToController function.
 *
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   KissMVC
 */
interface ControllerBuilderInterface
{
    /**
     * @return Controller
     */
    public static function create();
}
