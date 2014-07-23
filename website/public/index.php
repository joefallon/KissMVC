<?php
/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
use KissMVP\AutoLoader;
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
define('BASE_PATH',         realpath(__DIR__   . '/../'));
define('APP_PATH',          realpath(BASE_PATH . '/application'));
define('LIB_PATH',          realpath(BASE_PATH . '/lib'));
define('CACHE_PATH',        realpath(BASE_PATH . '/cache'));
define('CONFIG_PATH',       realpath(BASE_PATH . '/config'));
define('LOGS_PATH',         realpath(BASE_PATH . '/logs'));

define('PRESENTERS_PATH',   realpath(APP_PATH  . '/presenters'));
define('DOMAIN_PATH',       realpath(APP_PATH  . '/domain-classes'));
define('ENTITIES_PATH',     realpath(APP_PATH  . '/entities'));
define('MODELS_PATH',       realpath(APP_PATH  . '/models'));
define('TBL_GATEWAYS_PATH', realpath(APP_PATH  . '/table-gateways'));

// Set the application include paths.
set_include_path( get_include_path() . ':'
                . LIB_PATH           . ':'
                . PRESENTERS_PATH    . ':'
                . DOMAIN_PATH        . ':'
                . ENTITIES_PATH      . ':'
                . MODELS_PATH        . ':'
                . TBL_GATEWAYS_PATH );

// Load the router.
require_once(CONFIG_PATH . '/routes.php');

// Load the main application configuration.
require_once(LIB_PATH . '/KissMVP/Application.php');
Application::loadConfiguration(CONFIG_PATH . '/main.php');

// Initialize the class autoloader.
require_once(LIB_PATH . '/KissMVP/AutoLoader.php');
AutoLoader::registerAutoLoad();

// Perform application bootstrapping (e.g. initialize the database, etc).
require_once(APP_PATH . '/Bootstrapper.php');
Bootstrapper::bootstrap();

// Run the application.
Application::run();
