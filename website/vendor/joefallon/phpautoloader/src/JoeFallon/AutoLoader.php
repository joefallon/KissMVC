<?php
namespace JoeFallon;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2015 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class AutoLoader
{
    /** @var string */
    private $_classFilename;
    /** @var string[] */
    private $_includePaths;

    /**
     * This function is used to register the class autoloader for use. Call
     * this function near the beginning of the application. This function
     * is compatible with Composer autoloading.
     */
    public static function registerAutoLoad()
    {
        $autoLoader = new AutoLoader();
        spl_autoload_register(array($autoLoader, 'load'));
    }

    /**
     * This function is called by the PHP runtime to find and load a class for use.
     * Users of this class should not call this function.
     *
     * @param string $className
     *
     * @return bool
     */
    public function load($className)
    {
        $this->_classFilename = $className . '.php';
        $this->_includePaths  = explode(PATH_SEPARATOR, get_include_path());
        $classFound           = null;

        if(strpos($this->_classFilename, '\\') !== false)
        {
            $classFound = $this->searchForBackslashNamespacedClass();
        }
        elseif(strpos($this->_classFilename, '_') !== false)
        {
            $classFound = $this->searchForUnderscoreNamespacedClass();
        }
        else
        {
            $classFound = $this->searchForNonNamespacedClass();
        }

        return $classFound;
    }

    /*************************************************************************
     * Protected Functions
     *************************************************************************/

    /**
     * This function searches for classes that are namespaced using the modern
     * standard method of class namespacing.
     *
     * @example Zend\Exception
     *
     * @return bool Returns true if the class is found, otherwise false.
     */
    protected function searchForBackslashNamespacedClass()
    {
        $filename = $this->_classFilename;

        foreach($this->_includePaths as $includePath)
        {
            $className = str_replace('\\', '/', $filename);
            $filePath  = $includePath . DIRECTORY_SEPARATOR . $className;

            if(file_exists($filePath) == true)
            {
                require($filePath);

                return true;
            }
        }

        return false;
    }

    /**
     * This function searches for classes that are namespaced using underscores
     * for namespacing (e.g. PEAR, Zend 1).
     *
     * @example Zend_Exception
     *
     * @return bool Returns true if the class is found, otherwise false.
     */
    protected function searchForUnderscoreNamespacedClass()
    {
        $filename = $this->_classFilename;

        foreach($this->_includePaths as $includePath)
        {
            $className = str_replace('_', '/', $filename);
            $filePath  = $includePath . DIRECTORY_SEPARATOR . $className;

            if(file_exists($filePath) == true)
            {
                require($filePath);

                return true;
            }
        }

        return false;
    }

    /**
     * This function searches for classes that are not namespaced at all.
     *
     * @example ZendException
     *
     * @return bool Returns true if the class is found, otherwise false.
     */
    protected function searchForNonNamespacedClass()
    {
        $filename = $this->_classFilename;

        foreach($this->_includePaths as $includePath)
        {
            $filePath = $includePath . DIRECTORY_SEPARATOR . $filename;

            if(file_exists($filePath) == true)
            {
                require($filename);

                return true;
            }
        }

        return false;
    }
}
