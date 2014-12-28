<?php
namespace KissMVP;

require_once(LIB_PATH . '/KissMVP/FrontPresenter.php');
require_once(LIB_PATH . '/KissMVP/Presenter.php');
require_once(LIB_PATH . '/KissMVP/PresenterFactoryInterface.php');


/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class Application
{
    /** @var array */
    protected static $_config;


    /**
     * This function loads the configuration file from the specified
     * $configFilePath.
     *
     * @param string $configFilePath
     */
    public static function loadConfiguration($configFilePath)
    {
        $config = null;
        require_once($configFilePath);

        if(self::$_config != null)
        {
            self::$_config = array_merge(self::$_config, $config);
        }
        else
        {
            self::$_config = $config;
        }

        unset($config);
    }


    /**
     * Given the key $registryItemName this function returns the value saved
     * in the global registry.
     *
     * @param string $registryItemName
     *
     * @return mixed
     */
    public static function getRegistryItem($registryItemName)
    {
        if(isset(self::$_config[$registryItemName]))
        {
            return self::$_config[$registryItemName];
        }
        else
        {
            return null;
        }
    }


    /**
     * Given the $registryItemName, this function stores the given $registryItem
     * in the registry.
     *
     * @param string $registryItemName
     * @param mixed  $registryItem
     */
    public static function setRegistryItem($registryItemName, $registryItem)
    {
        self::$_config[$registryItemName] = $registryItem;
    }


    /**
     * This function runs the application.
     */
    public static function run()
    {
        self::checkSsl();
        self::setTimeZone();

        $frontPresenter = new FrontPresenter();
        $frontPresenter->routeRequest();
    }


    /**
     * This method ensures the application redirects to the ssl version of
     * the site if the configuration 'ssl_required' is set to true.
     */
    protected static function checkSsl()
    {
        $config = self::$_config;

        if(!isset($config['ssl_required']) || $config['ssl_required'] == false)
        {
            // SSL is not required.
            return;
        }

        if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on')
        {
            // SSL is not active. Therefore, perform redirect to activate it.
            $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $url);
            exit;
        }
    }


    /**
     * This method sets the default timezone to the value specified in the
     * application configuration.
     */
    protected static function setTimeZone()
    {
        $timeZone = self::$_config['timezone'];
        date_default_timezone_set($timeZone);
    }
}

