<?php
namespace KissMVP;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   KissMVP
 */
class AutoLoader
{
    const PHP_FILE_EXT = '.php';

    /** @var string */
    private $_classFilename;
    /** @var string[] */
    private $_includePaths;


    /**
     * This function is used to start the autoloader.
     */
    public static function registerAutoLoad()
    {
        $autoLoader = new AutoLoader();
        spl_autoload_register(array($autoLoader, 'load'));
    }


    /**
     * This method is called by PHP when a class needs to be found and loaded.
     *
     * @param string $className
     *
     * @return bool
     */
    public function load($className)
    {
        $this->_classFilename = $className . self::PHP_FILE_EXT;
        $this->_includePaths  = explode(PATH_SEPARATOR, get_include_path());
        $classFound           = null;

        if(strpos($this->_classFilename, '\\') !== false)
        {
            $classFound = $this->searchForBackslashNamespacedClass();
            if($classFound)
            {
                return true;
            }
        }
        elseif(strpos($this->_classFilename, '_') !== false)
        {
            $classFound = $this->searchForUnderscoreNamespacedClass();
            if($classFound)
            {
                return true;
            }
        }
        else
        {
            $classFound = $this->searchForNonNamespacedClass();
            if($classFound)
            {
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    protected function searchForNonNamespacedClass()
    {
        $filename = $this->_classFilename;

        // Search through the include paths for the file.
        foreach($this->_includePaths as $includePath)
        {
            $filePath = $includePath . DIRECTORY_SEPARATOR . $filename;

            if(file_exists($filePath))
            {
                require($filename);

                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    protected function searchForUnderscoreNamespacedClass()
    {
        $filename = $this->_classFilename;

        foreach($this->_includePaths as $includePath)
        {
            $className = str_replace('_', '/', $filename);
            $filePath  = $includePath . DIRECTORY_SEPARATOR . $className;

            if(file_exists($filePath))
            {
                require($filePath);

                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    protected function searchForBackslashNamespacedClass()
    {
        $filename = $this->_classFilename;

        foreach($this->_includePaths as $includePath)
        {
            $className = str_replace('\\', '/', $filename);
            $filePath  = $includePath . DIRECTORY_SEPARATOR . $className;

            if(file_exists($filePath))
            {
                require($filePath);

                return true;
            }
        }

        return false;
    }
}
