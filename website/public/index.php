<?php
use JoeFallon\AutoLoader;
use KissMVC\Application;

// Define the application environment.
if(getenv('APPLICATION_ENV') == null)
{
    // Default to the production environment settings due to being safer.
    define('APPLICATION_ENV', 'production');
    ini_set('display_errors', 0);
}
else
{
    define('APPLICATION_ENV', getenv('APPLICATION_ENV'));
    ini_set('display_errors', 1);
}

// Define the include paths.
define('BASE_PATH', realpath(__DIR__   . '/../'));
define('APP_PATH',  BASE_PATH . '/application');

// Set the application include paths.
set_include_path( get_include_path() . ':'
                 . BASE_PATH . '/lib:'
                 . APP_PATH  . '/controllers:'
                 . APP_PATH  . '/domain:'
                 . APP_PATH  . '/entities:'
                 . APP_PATH  . '/gateways:'
                 . APP_PATH  . '/models' );

// Load the main application configuration.
require_once(BASE_PATH . '/lib/KissMVC/Application.php');
Application::loadConfiguration(APP_PATH . '/config/main.php');

// Initialize the class autoloader.
require_once(BASE_PATH . '/vendor/autoload.php');
AutoLoader::registerAutoLoad();

// Perform application bootstrapping (e.g. initialize the database, etc).
require_once(BASE_PATH . '/application/Bootstrapper.php');
Bootstrapper::bootstrap();

// Run the application.
Application::run();
