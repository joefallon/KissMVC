<?php
use KissMVP\AutoLoader;

// Set the timezone.
date_default_timezone_set('UTC');

// Specify the application pathing.
define('BASE_PATH', realpath(__DIR__ . '/../../'));
define('LOG_NAME', date('Y-m-d') . '.log');
define('LOG_PATH', realpath(BASE_PATH . '/tests/logs') . '/' . LOG_NAME);

// Library Include Paths
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/tests/lib'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/lib'));

// Tests Include Paths
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/tests/application/controllers'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/tests/application/domain-classes'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/tests/application/entities'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/tests/application/models'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/tests/application/table-gateways'));

// Application Include Paths
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/application/controllers'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/application/domain-classes'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/application/entities'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/application/models'));
set_include_path(get_include_path() . PATH_SEPARATOR
                 . realpath(BASE_PATH . '/application/table-gateways'));

// Database Connection Parameters
define('DB_NAME', 'dbname');
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');

require_once(BASE_PATH . '/lib/KissMVP/AutoLoader.php');
AutoLoader::registerAutoLoad();

