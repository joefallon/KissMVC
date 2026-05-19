<?php
declare(strict_types=1);
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
 * Optional convenience builder for constructing a FrontController with test seams.
 */
final class FrontControllerBuilder
{
    private FrontControllerOptions $options;

    public function __construct(?FrontControllerOptions $options = null)
    {
        $this->options = $options ?? new FrontControllerOptions();
    }

    public function withRequestParametersProvider(RequestParametersProviderInterface $requestParametersProvider): self
    {
        $this->options->requestParametersProvider = $requestParametersProvider;

        return $this;
    }

    public function withRouteResolver(RouteResolverInterface $routeResolver): self
    {
        $this->options->routeResolver = $routeResolver;

        return $this;
    }

    public function withHeadersSentChecker(HeadersSentCheckerInterface $headersSentChecker): self
    {
        $this->options->headersSentChecker = $headersSentChecker;

        return $this;
    }

    public function withHeaderEmitter(HeaderEmitterInterface $headerEmitter): self
    {
        $this->options->headerEmitter = $headerEmitter;

        return $this;
    }

    public function build(): FrontController
    {
        return new FrontController($this->options);
    }
}
