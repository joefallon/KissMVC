<?php
declare(strict_types=1);

use KissMVC\Application;

// Define the application environment.
if(getenv('APPLICATION_ENV') == null)
{
    define('APPLICATION_ENV', 'production');
    ini_set('display_errors', '0');
}
else
{
    define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
    ini_set('display_errors', '1');
}

define('BASE_PATH', realpath(__DIR__   . '/../'));
const APP_PATH = BASE_PATH . '/src';

require_once(BASE_PATH . '/lib/KissMVC/Application.php');
require_once(BASE_PATH . '/src/Bootstrapper.php');
require_once(BASE_PATH . '/vendor/autoload.php');

Application::loadConfiguration(APP_PATH . '/Config/main.php');
Bootstrapper::bootstrap();
Application::run();
