<?php
/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2015 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
use KissMVC\Controller;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->setPageTitle('Index');
        $this->setLayout('default.php');
        $this->setViewFileName('index.php');
    }

    public function execute() { }

    /**
     * @return string
     */
    public function getMessage()
    {
        return 'Hello, World!';
    }
}
