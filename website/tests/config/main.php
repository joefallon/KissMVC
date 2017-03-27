<?php
use JoeFallon\AutoLoader;

// Set the timezone.
date_default_timezone_set('UTC');

// Specify the application base pathing.
define('BASE_PATH',  realpath(__DIR__ . '/../../'));
define('APP_PATH',   BASE_PATH . '/application');
define('TESTS_PATH', BASE_PATH . '/tests');


// Set the library include paths.
set_include_path( get_include_path() . ':'
                  . BASE_PATH  . '/lib:'
                  . TESTS_PATH . '/lib' );

// Set the application include paths.
set_include_path( get_include_path() . ':'
                  . APP_PATH . '/controllers:'
                  . APP_PATH . '/domain:'
                  . APP_PATH . '/entities:'
                  . APP_PATH . '/gateways:'
                  . APP_PATH . '/models');

// Set the tests include paths.
set_include_path( get_include_path() . ':'
                  . TESTS_PATH . '/controllers:'
                  . TESTS_PATH . '/domain:'
                  . TESTS_PATH . '/entities:'
                  . TESTS_PATH . '/gateways:'
                  . TESTS_PATH . '/models');

// Set the testing database connection parameters.
define('DB_NAME', 'dbname');
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// Load the autoloader.
require_once(BASE_PATH . '/vendor/autoload.php');
AutoLoader::registerAutoLoad();

