<?php
/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2015 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
use KissMVC\Controller;

class PageWithParametersController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->setPageTitle('Page with Parameters');
        $this->setLayout('default.php');
        $this->setView('page-with-parameters.php');
    }

    public function execute()
    {
    }
}
