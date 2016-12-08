<?php
$config = array();

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
|
|
|
*/
if(APPLICATION_ENV == 'production')
{
    // Production Database Settings
    $config['db'] = array(
        'name' => 'db-name',
        'host' => 'db-host',
        'user' => 'db-username',
        'pass' => 'db-password'
    );
}
else
{
    // Development Database Settings
    $config['db'] = array(
        'name' => 'db-name',
        'host' => 'db-host',
        'user' => 'db-username',
        'pass' => 'db-password'
    );
}

/*
|--------------------------------------------------------------------------
| Secret Key
|--------------------------------------------------------------------------
|
| This is the secret key. It is used for generating various security
| hashes and etc.
|
*/
$config['secret_key'] = 'place-your-super-secret-key-here';

/*
|--------------------------------------------------------------------------
| SSL is Required
|--------------------------------------------------------------------------
|
| Setting this to true will force the entire site to use SSL.
|
*/
$config['ssl_required'] = false;

/*
|--------------------------------------------------------------------------
| Timezone to store times.
|--------------------------------------------------------------------------
|
| Use UTC.
|
*/
$config['timezone'] = 'UTC';

/*
|--------------------------------------------------------------------------
| Views Directory
|--------------------------------------------------------------------------
|
*/
$config['views_directory'] = BASE_PATH . '/application/views';

/*
|--------------------------------------------------------------------------
| View Partials Directory
|--------------------------------------------------------------------------
|
*/
$config['partials_directory'] = BASE_PATH . '/application/view-partials';

/*
|--------------------------------------------------------------------------
| Layouts Directory
|--------------------------------------------------------------------------
|
*/
$config['layouts_directory'] = BASE_PATH . '/application/layouts';


/*
|--------------------------------------------------------------------------
| Place your additional application configuration here.
|--------------------------------------------------------------------------
*/
