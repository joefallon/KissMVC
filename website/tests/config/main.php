<?php
use JoeFallon\AutoLoader;

// Set the timezone.
date_default_timezone_set('UTC');

// Specify the application base pathing.
define('BASE_PATH', realpath(__DIR__ . '/../../'));

// Set the path for the log file.
define('LOG_PATH',  BASE_PATH . '/tests/logs/' . date('Y-m-d') . '.log');

// Set the library include paths.
set_include_path( get_include_path()         . ':'
                  . BASE_PATH . '/tests/lib' . ':'
                  . BASE_PATH . '/lib' );

// Set the tests include paths.
set_include_path( get_include_path()                                . ':'
                  . BASE_PATH . '/tests/application/controllers'    . ':'
                  . BASE_PATH . '/tests/application/domain-classes' . ':'
                  . BASE_PATH . '/tests/application/entities'       . ':'
                  . BASE_PATH . '/tests/application/models'         . ':'
                  . BASE_PATH . '/tests/application/table-gateways' );

// Set the application include paths.
set_include_path( get_include_path()                          . ':'
                  . BASE_PATH . '/application/controllers'    . ':'
                  . BASE_PATH . '/application/domain-classes' . ':'
                  . BASE_PATH . '/application/entities'       . ':'
                  . BASE_PATH . '/application/models'         . ':'
                  . BASE_PATH . '/application/table-gateways' );

// Set the testing database connection parameters.
define('DB_NAME', 'dbname');
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// Load the autoloader.
require_once(BASE_PATH . '/vendor/autoload.php');
AutoLoader::registerAutoLoad();

