<?php
declare(strict_types=1);

/**
 * Copyright (c) 2025 Joseph Fallon <joseph.t.fallon@gmail.com>
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

namespace Application\Controllers;

use KissMVC\Controller;

/**
 * IndexController
 *
 * Example controller shipped with the skeleton application. This file is
 * intentionally simple and well-documented so new contributors can copy and
 * modify it when adding application-specific pages.
 *
 * Responsibilities (KISS):
 *  - Configure the layout and view file for the page.
 *  - Prepare any page-specific data during execute().
 *  - Expose small helper methods used by views (for example getMessage()).
 *
 * How to customize:
 *  - Change the page title with setPageTitle("My Page").
 *  - Change the layout/view file names with setLayout("my-layout.php") and
 *    setView("my-view.php"). These paths are resolved relative to the
 *    configured 'layouts_directory' and 'views_directory' in config/main.php.
 *  - To include CSS/JS use addCssFile("styles.css") and
 *    addJavaScriptFile("bundle.js"). The layout template should reference
 *    the controller's getCssFiles()/getJavaScriptFiles() methods.
 *  - Read request parameters with getRequestParameters() (ordered array).
 *
 * Conventions:
 *  - Keep controller methods small and focused; put business logic in models
 *    or services instead of controllers when practical.
 */
class IndexController extends Controller
{
    /**
     * Construct and configure the controller. Child classes should call
     * parent::__construct() and then perform lightweight setup.
     */
    public function __construct()
    {
        parent::__construct();

        // Page metadata and template selection. Edit these to suit your page.
        $this->setPageTitle('Index');
        $this->setLayout('default.php');
        $this->setView('index.php');

        // Example: add CSS or JS files that the layout will include.
        // $this->addCssFile('styles/site.css');
        // $this->addJavaScriptFile('js/site.js');
    }

    /**
     * Execute business logic for this page. This method is called by the
     * FrontController after request routing and before the layout is rendered.
     *
     * Keep this method small: gather data and assign it to properties or
     * expose via public helpers so the view can render it.
     */
    public function execute(): void
    {
        // Call the parent's execute() as a no-op hook. The base implementation
        // is intentionally empty; calling it here documents our intent and
        // prevents IDE/static-analyzer warnings that complain when an override
        // does not call the parent. It is harmless and future-proofs the
        // controller should the base implementation gain behavior.
        parent::execute();

        // Default: no additional processing required for the example index
        // page. Override or extend this method when you add page-specific
        // logic (database calls, service orchestration, etc.).
    }

    /**
     * Example helper used by the view. Keep helpers tiny and focused; views
     * should be mostly presentation logic.
     */
    public function getMessage(): string
    {
        return 'Hello, World!';
    }

    /**
     * Convenience: read a single request parameter by position (0-based).
     * Returns null when the parameter does not exist. This demonstrates how
     * controllers can safely access the request parameters prepared by the
     * front controller.
     *
     * @param int $index Zero-based index of the parameter to retrieve.
     * @return string|null
     */
    public function getRequestParameter(int $index = 0): ?string
    {
        $params = $this->getRequestParameters();

        return $params[$index] ?? null;
    }
}
