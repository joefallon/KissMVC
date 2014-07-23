<?php
use KissMVP\Controller;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class IndexController extends Controller
{
    public function  __construct()
    {
        parent::__construct();

        $this->setPageTitle('Index');
        $this->setLayout('default.php');
        $this->setViewFileName('index.php');
    }

    public function execute() { }
    
    public function getMessage()
    {
        return 'Hello, World!';
    }
}
