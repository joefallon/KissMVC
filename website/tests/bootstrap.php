<?php
declare(strict_types=1);

// Enable strict error reporting for tests.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Only set timezone when none is configured.
if(!ini_get('date.timezone'))
{
    date_default_timezone_set('UTC');
}

// Resolve and validate base path.
//$basePath = realpath(__DIR__ . '/../../');
//
//if($basePath === false)
//{
//    throw new RuntimeException('Could not resolve BASE_PATH from `' . __FILE__ . '`');
//}
//
//$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
//
//if(!defined('BASE_PATH'))
//{
//    define('BASE_PATH', $basePath);
//}
//
//if(!defined('APP_PATH'))
//{
//    define('APP_PATH', BASE_PATH . '/Application');
//}
//
//if(!defined('TESTS_PATH'))
//{
//    define('TESTS_PATH', BASE_PATH . '/Tests');
//}

const DB_NAME = 'dbname';
const DB_HOST = 'localhost';
const DB_USER = 'username';
const DB_PASS = 'password';

// Require Composer autoloader and fail fast if missing.
$autoload = BASE_PATH . '/vendor/autoload.php';

if(!file_exists($autoload))
{
    throw new RuntimeException('Composer autoload not found at: ' . $autoload . '. Run `composer install`.');
}

require_once $autoload;
