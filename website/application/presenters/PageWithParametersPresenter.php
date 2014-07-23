<?php
use KissMVP\Presenter;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class PageWithParametersPresenter extends Presenter
{
    public function  __construct()
    {
        parent::__construct();

        $this->setPageTitle('Page with Parameters');
        $this->setLayout('default.php');
        $this->setViewFileName('page-with-parameters.php');
    }

    public function execute() { }
}