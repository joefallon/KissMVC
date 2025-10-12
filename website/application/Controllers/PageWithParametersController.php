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
 * PageWithParametersController
 *
 * Example controller that demonstrates reading URL parameters supplied by the
 * FrontController. This controller is intentionally small and documented so
 * contributors can adapt it to their needs.
 *
 * Usage notes:
 *  - Request parameters are an ordered array of strings. For a request such
 *    as '/page-with-parameters/foo/123' the parameters will be ['foo','123'].
 *  - Access parameters using getRequestParameters() or the convenience
 *    getRequestParameter($index) shown below.
 *  - Keep heavy business logic out of the controller; prefer services/models.
 */
class PageWithParametersController extends Controller
{
    /**
     * Configure page metadata and templates. Keep constructor lightweight.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setPageTitle('Page with Parameters');
        $this->setLayout('default.php');
        $this->setView('page-with-parameters.php');
    }

    /**
     * Perform page-specific processing. Called by FrontController.
     *
     * We call parent::execute() as a deliberate no-op hook to document intent
     * and to silence static analyzers that expect the parent hook to be
     * invoked. This is harmless and future-proofs the controller.
     */
    public function execute(): void
    {
        // Call the parent's no-op hook. See comment above.
        parent::execute();

        // Example: read parameters and perform trivial processing. Replace
        // with real logic (database calls, services, validation, etc.).
        $params = $this->getRequestParameters();

        // You can use $params directly in views or expose helpers as needed.
        // For example, to expose the first parameter as a helper, you could
        // store it in a property here and add a getter.
    }

    /**
     * Convenience helper: return a specific request parameter by index.
     *
     * @param int $index Zero-based index of the parameter to retrieve.
     * @return string|null Parameter string or null when it does not exist.
     */
    public function getRequestParameter(int $index = 0): ?string
    {
        $params = $this->getRequestParameters();

        return $params[$index] ?? null;
    }
}
