<?php
declare(strict_types=1);

$basePath = dirname(__DIR__);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath);
}

if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/src');
}

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', BASE_PATH . '/tests');
}

$autoload = BASE_PATH . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException('Composer autoload not found at ' . $autoload . '. Run composer install before testing.');
}

require_once $autoload;
