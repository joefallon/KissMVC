<?php
use KissMVC\Application;
use Application\Bootstrapper;

// Define the application environment.
if(getenv('APPLICATION_ENV') == null)
{
    define('APPLICATION_ENV', 'production');
    ini_set('display_errors', 0);
}
else
{
    define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
    ini_set('display_errors', 1);
}

define('BASE_PATH', realpath(__DIR__   . '/../'));
define('APP_PATH',  BASE_PATH . '/application');

require_once(BASE_PATH . '/vendor/autoload.php');

Application::loadConfiguration(APP_PATH . '/config/main.php');
Bootstrapper::bootstrap();
Application::run();
