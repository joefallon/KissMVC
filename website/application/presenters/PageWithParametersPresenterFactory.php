<?php
use KissMVP\PresenterFactoryInterface;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class PageWithParametersPresenterFactory implements PresenterFactoryInterface
{
    public static function create()
    {
        return new PageWithParametersPresenter();
    }
}
