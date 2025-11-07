<?php declare(strict_types=1);
/**
 * Copyright (c) 2015-2025 Joseph Fallon <joseph.t.fallon@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace KissMVC;

/**
 * Abstract base controller used by page controllers.
 *
 * Responsibilities (KISS):
 *  - Manage view/layout file paths.
 *  - Provide collections for CSS and JS assets.
 *  - Hold request parameters and page title.
 *  - Render views, layouts and partials.
 *
 * Design goals:
 *  - Small, well-named methods (Clean Code).
 *  - Explicit types (PHP 7.4 typed properties and signatures).
 *  - Minimal surprises: methods do what their names imply.
 */
abstract class Controller
{
    /** Fully-qualified path to layout file. */
    private string $layoutPath = '';

    /** Fully-qualified path to view file. */
    private string $viewPath = '';

    /** CSS asset file names (relative to public or configured dir). */
    private array $cssFiles = [];

    /** JavaScript asset file names (relative to public or configured dir). */
    private array $jsFiles = [];

    /** Page title for the rendered page. */
    private ?string $pageTitle = null;

    /** Request parameters parsed from the URL (ordered list). */
    private array $requestParameters = [];

    /**
     * Constructor - initializes collections. Keep lightweight.
     */
    public function __construct()
    {
        // Properties already have sensible typed defaults; constructor is
        // intentionally minimal to avoid hidden side effects.
    }

    /**
     * Perform page specific processing. Override in child controllers.
     * Called immediately after controller instantiation.
     */
    public function execute(): void
    {
        // Default: no-op. Child controllers may and probably should override.
    }

    /**
     * Render the selected layout file. This will "require" the file so it
     * executes in the current scope (allowing templates to access controller
     * members if needed).
     */
    public function renderLayout(): void
    {
        include $this->layoutPath;
    }

    /**
     * Render the selected view file.
     */
    public function renderView(): void
    {
        include $this->viewPath;
    }

    /**
     * Render a partial template located in the configured partials directory.
     *
     * @param string $fileName Relative file name (e.g. 'header.php').
     * @param array  $data Optional associative array of variables to expose to
     *                    the partial. The partial may extract these manually.
     */
    public function renderPartial(string $fileName, array $data = []): void
    {
        $dir = Application::getRegistryItem('partials_directory');
        $partialsDir = is_string($dir) ? $dir : '';

        $filePath = $partialsDir . DIRECTORY_SEPARATOR . $fileName;

        include $filePath;
    }

    /**
     * Return configured CSS files. The layout uses this to include CSS.
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    /**
     * Return configured JavaScript files. The layout uses this to include JS.
     */
    public function getJavaScriptFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * Return application version from the registry. May be null when missing.
     */
    public function getVersion(): ?string
    {
        $version = Application::getRegistryItem('version');

        return is_string($version) ? $version : null;
    }

    /**
     * Return the page title, or null when not set.
     */
    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    /**
     * Return request parameters as an ordered array of strings.
     * Example: ['/page', 'abc', '123'] -> ['abc','123']
     */
    public function getRequestParameters(): array
    {
        return $this->requestParameters;
    }

    /**
     * Set request parameters parsed from the routing layer.
     *
     * @param array $requestParameters Ordered array of strings.
     */
    public function setRequestParameters(array $requestParameters): void
    {
        $this->requestParameters = $requestParameters;
    }

    /*************************************************************************
     * Protected helpers: small methods used by concrete controllers.
     *************************************************************************/

    /**
     * Set the layout file by name. Resolves the configured layout directory.
     *
     * @param string $layoutFileName File name relative to layouts directory.
     */
    protected function setLayout(string $layoutFileName): void
    {
        $dir = Application::getRegistryItem('layouts_directory');
        $layoutsDir = is_string($dir) ? $dir : '';

        $this->layoutPath = $layoutsDir . DIRECTORY_SEPARATOR . $layoutFileName;
    }

    /**
     * Set the view file by name. Resolves the configured views directory.
     *
     * @param string $viewFileName File name relative to views directory.
     */
    protected function setView(string $viewFileName): void
    {
        $dir = Application::getRegistryItem('views_directory');
        $viewsDir = is_string($dir) ? $dir : '';

        $this->viewPath = $viewsDir . DIRECTORY_SEPARATOR . $viewFileName;
    }

    /**
     * Add a CSS file name to the collection.
     *
     * @param string $cssFile File name or path fragment for the CSS file.
     */
    protected function addCssFile(string $cssFile): void
    {
        $this->cssFiles[] = $cssFile;
    }

    /**
     * Add a JavaScript file name to the collection.
     *
     * @param string $jsFile File name or path fragment for the JS file.
     */
    protected function addJavaScriptFile(string $jsFile): void
    {
        $this->jsFiles[] = $jsFile;
    }

    /**
     * Set the page title.
     *
     * @param string $pageTitle
     */
    protected function setPageTitle(string $pageTitle): void
    {
        $this->pageTitle = $pageTitle;
    }
}
