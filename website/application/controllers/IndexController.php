<?php
use KissMVC\Controller;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->setPageTitle('Index');
        $this->setLayout('default.php');
        $this->setView('index.php');
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
