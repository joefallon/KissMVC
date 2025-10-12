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

namespace KissMVC;

/**
 * ControllerFactoryInterface
 *
 * Purpose
 * -------
 * A small factory interface that guarantees a Controller instance can be
 * created by the routing layer. Factories implementing this interface are
 * responsible only for constructing and returning a Controller instance.
 *
 * Design notes (Clean Code / KISS):
 * - Keep factory methods small and side-effect free.
 * - Avoid sending headers, printing output, or performing long-running work
 *   during creation. Those actions belong to the controller's execution.
 * - The returned Controller should be ready to execute immediately.
 *
 * Usage example
 * -------------
 * class IndexControllerFactory implements ControllerFactoryInterface
 * {
 *     public static function create(): Controller
 *     {
 *         return new IndexController();
 *     }
 * }
 */
interface ControllerFactoryInterface
{
    /**
     * Create and return a Controller instance.
     *
     * Implementations must return an object that implements Controller. The
     * method should not echo, send headers, or perform heavy initialization.
     *
     * @return Controller
     */
    public static function create(): Controller;
}
