<?php
$config = array();

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your KissMVP root. Typically this will be your base URL,
| ***WITHOUT*** a trailing slash:
|
|	http://example.com
|
*/
if(APPLICATION_ENV == 'production')
{
    // Production URL
    $config['base_url']	= 'http://production-url';
}
else
{
    // Development URL
    $config['base_url']	= 'http://localhost:10080/KissMVP/website/public';
}

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
| Use UTC. Todo: Clean this up.
| (e.g. America/Anchorage)
|
*/
$config['timezone'] = 'UTC';

/*
|--------------------------------------------------------------------------
| Views Directory
|--------------------------------------------------------------------------
|
*/
$config['views_directory'] = APP_PATH . '/views';

/*
|--------------------------------------------------------------------------
| View Partials Directory
|--------------------------------------------------------------------------
|
*/
$config['partials_directory'] = APP_PATH . '/view-partials';

/*
|--------------------------------------------------------------------------
| Layouts Directory
|--------------------------------------------------------------------------
|
*/
$config['layouts_directory'] = APP_PATH . '/layouts';

