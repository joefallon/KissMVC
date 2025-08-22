<?php

// Set the timezone.
date_default_timezone_set('UTC');

// Specify the application base pathing.
define('BASE_PATH',  realpath(__DIR__ . '/../../'));
define('APP_PATH',   BASE_PATH . '/application');
define('TESTS_PATH', BASE_PATH . '/tests');

// Set the testing database connection parameters.
define('DB_NAME', 'dbname');
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');

// Load the autoloader.
require_once(BASE_PATH . '/vendor/autoload.php');


