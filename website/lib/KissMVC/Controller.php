<?php
namespace KissMVC;

require_once(BASE_PATH . '/lib/KissMVC/Application.php');

abstract class Controller
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
     * empty and not overridden.
     */
    public function execute() { }

    /**
     * This function renders the selected layout.
     */
    public function renderLayout()
    {
        /** @noinspection PhpIncludeInspection */
        require($this->_layoutPath);
    }

    /**
     * This function renders the selected view.
     */
    public function renderView()
    {
        /** @noinspection PhpIncludeInspection */
        require($this->_viewPath);
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
        /** @noinspection PhpIncludeInspection */
        require("$dir/$fileName");
    }

    /**
     * This function returns an array containing the names of all of
     * the CSS files. Iterate through the array of CSS files in the
     * layout so they may all be included on the page.
     *
     * @return array
     */
    public function getCssFiles()
    {
        return $this->_cssFiles;
    }

    /**
     * This function returns an array containing the names of all of
     * the JavaScript files. Iterate through the array of JavaScript files
     * in the layout so they may all be included on the page.
     *
     * @return array
     */
    public function getJavaScriptFiles()
    {
        return $this->_jsFiles;
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
     * occur in an array in the same order in which they appeared in the
     * request URL.
     *
     * Example: http://www.mysite.com/page-with-parameters/abc/123/xyz
     *
     * Array
     * (
     *   [0] => abc
     *   [1] => 123
     *   [2] => xyz
     * )
     *
     * @return array
     */
    public function getRequestParameters()
    {
        return $this->_requestParameters;
    }

    /**
     * This function sets the request parameters. The request parameters
     * consists of an array of strings. The first parameter after the
     * controller name segment has an index of 0. The second has an index
     * of 1, etc.
     *
     * Example: http://www.mysite.com/page-with-parameters/abc/123/xyz
     *
     * Array
     * (
     *   [0] => abc
     *   [1] => 123
     *   [2] => xyz
     * )
     *
     * @param array $requestParameters
     */
    public function setRequestParameters($requestParameters)
    {
        $this->_requestParameters = $requestParameters;
    }

    /*************************************************************************
     * Protected Functions
     *************************************************************************/

    /**
     * This function sets the layout filename.
     *
     * @param string $layoutFileName
     */
    protected function setLayout($layoutFileName)
    {
        $dir = Application::getRegistryItem('layouts_directory');
        $this->_layoutPath = "$dir/$layoutFileName";
    }

    /**
     * This function sets the view filename.
     *
     * @param string $viewFileName
     */
    protected function setView($viewFileName)
    {
        $dir = Application::getRegistryItem('views_directory');
        $this->_viewPath = "$dir/$viewFileName";
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
        $pageTitle = strval($pageTitle);
        $this->_pageTitle = $pageTitle;
    }
}
