<?php
namespace KissMVC;

/**
 * All Controller factories must implement this interface to ensure they can
 * be created by the routeToController function.
 */
interface ControllerFactoryInterface
{
    /**
     * @return Controller
     */
    public static function create();
}
