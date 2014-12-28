<?php
/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
use JoeFallon\AutoLoader;
use KissMVP\Application;

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
define('BASE_PATH',   realpath(__DIR__   . '/../'));
define('APP_PATH',    BASE_PATH . '/application');
define('CACHE_PATH',  BASE_PATH . '/cache');
define('CONFIG_PATH', BASE_PATH . '/config');
define('LIB_PATH',    BASE_PATH . '/lib');
define('LOGS_PATH',   BASE_PATH . '/logs');
define('VENDOR_PATH', BASE_PATH . '/vendor');

// Set the application include paths.
set_include_path( get_include_path()    . ':'
                . BASE_PATH . '/lib'    . ':'
                . BASE_PATH . '/vendor' . ':'
                . BASE_PATH . '/application/presenters'     . ':'
                . BASE_PATH . '/application/domain-classes' . ':'
                . BASE_PATH . '/application/entities'       . ':'
                . BASE_PATH . '/application/models'         . ':'
                . BASE_PATH . '/application/table-gateways' );

// Load the router.
require_once(CONFIG_PATH . '/routes.php');

// Load the main application configuration.
require_once(LIB_PATH . '/KissMVP/Application.php');
Application::loadConfiguration(CONFIG_PATH . '/main.php');

// Initialize the class autoloader.
require_once(VENDOR_PATH . '/autoload.php');
Autoloader::registerAutoLoad();

// Perform application bootstrapping (e.g. initialize the database, etc).
require_once(APP_PATH . '/Bootstrapper.php');
Bootstrapper::bootstrap();

// Run the application.
Application::run();
