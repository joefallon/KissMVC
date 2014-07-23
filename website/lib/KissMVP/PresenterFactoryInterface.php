<?php
namespace KissMVP;

/**
 * All presenter factories must implement this interface to ensure they can
 * be created by the routeToPresenter function.
 *
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   KissMVP
 */
interface PresenterFactoryInterface
{
    /**
     * @return Presenter
     */
    public static function create();
}
