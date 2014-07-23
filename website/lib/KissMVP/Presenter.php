<?php
namespace KissMVP;

require_once(LIB_PATH . '/KissMVP/Application.php');

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   KissMVP
 */
abstract class Presenter
{
    /** @var string */
    private $_layoutPath;
    /** @var string */
    private $_viewPath;
    /** @var string[] */
    private $_cssFiles;
    /** @var string[] */
    private $_jsFiles;
    /** @var string */
    private $_pageTitle;
    /** @var array */
    private $_requestParameters;


    public function __construct()
    {
        $this->_cssFiles = array();
        $this->_jsFiles  = array();
    }


    /**
     * This function performs any page specific processing associated with
     * the page. It is called immediately after the class constructor is
     * called. If a page performs no processing, then this function may be
     * empty.
     */
    public function execute() { }


    /**
     * This function renders the selected layout.
     */
    public function renderLayout()
    {
        require($this->_layoutPath);
    }


    /**
     * This function renders the selected view.
     */
    public function renderView()
    {
        require($this->_viewPath);
    }


    /**
     * This function returns an array containing the names of all of
     * the CSS files.
     *
     * @return array
     */
    public function getCssFiles()
    {
        return $this->_cssFiles;
    }


    /**
     * This function returns an array containing the names of all of
     * the JavaScript files.
     *
     * @return array
     */
    public function getJavaScriptFiles()
    {
        return $this->_jsFiles;
    }


    /**
     * This function renders the specified partial view.
     *
     * @param string $fileName
     * @param array  $data
     */
    public function renderPartial($fileName, $data = array())
    {
        $dir = Application::getRegistryItem('partials_directory');

        require($dir . '/' . $fileName);
    }


    /**
     * This function returns the base URL of the application.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return Application::getRegistryItem('base_url');
    }


    /**
     * This function returns the application version. It is most useful
     * to ensure the latest version of static files are downloaded.
     *
     * @return string
     */
    public function getVersion()
    {
        return Application::getRegistryItem('version');
    }


    /**
     * This function returns the page title.
     *
     * @return string
     */
    public function getPageTitle()
    {
        return $this->_pageTitle;
    }


    /**
     * This function returns the request parameters of the request. They
     * occur in the array in the same order in which they appeared in the
     * request URL.
     *
     * @return array
     */
    public function getRequestParameters()
    {
        return $this->_requestParameters;
    }


    /**
     * This function sets the request parameters.
     *
     * @param array $requestParameters
     */
    public function setRequestParameters($requestParameters)
    {
        $this->_requestParameters = $requestParameters;
    }


    /**
     * This function returns the value of the specified GET parameter if
     * it exists and null otherwise.
     *
     * @param string $key
     *
     * @return null|string
     */
    public function getQueryParam($key)
    {
        if(isset($_GET[$key]) == true)
        {
            return $_GET[$key];
        }

        return null;
    }


    /**
     * This function returns the value of the specified POST parameter if
     * it exists and null otherwise.
     *
     * @param string $key
     *
     * @return null|string
     */
    public function getPostParam($key)
    {
        if(isset($_POST[$key]) == true)
        {
            return $_POST[$key];
        }

        return null;
    }


    /**
     * This function sets the layout filename.
     *
     * @param string $layoutFileName
     */
    protected function setLayout($layoutFileName)
    {
        $dir = Application::getRegistryItem('layouts_directory');
        $this->_layoutPath = $dir . '/' . $layoutFileName;
    }


    /**
     * This function sets the view filename.
     *
     * @param string $viewFileName
     */
    protected function setViewFileName($viewFileName)
    {
        $dir = Application::getRegistryItem('views_directory');
        $this->_viewPath = $dir . '/' . $viewFileName;
    }


    /**
     * This function adds the specified CSS file to the CSS file collection.
     *
     * @param string $cssFile
     */
    protected function addCssFile($cssFile)
    {
        $this->_cssFiles[] = $cssFile;
    }


    /**
     * This function adds the specified JavaScript file to the JavaScript file
     * collection.
     *
     * @param string $jsFile
     */
    protected function addJavaScriptFile($jsFile)
    {
        $this->_jsFiles[] = $jsFile;
    }


    /**
     * This function sets the page title to the specified value.
     *
     * @param string $pageTitle
     */
    protected function setPageTitle($pageTitle)
    {
        $pageTitle        = strval($pageTitle);
        $this->_pageTitle = $pageTitle;
    }
}
