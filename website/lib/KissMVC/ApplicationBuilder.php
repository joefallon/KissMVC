<?php declare(strict_types=1);
/**
 * Copyright (c) 2015-2025 Joseph Fallon <joseph.t.fallon@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
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

use Closure;

/**
 * Small builder for ApplicationRunner dependencies.
 */
final class ApplicationBuilder
{
    private ?Closure $frontControllerFactory = null;
    private ?Closure $headersSentChecker = null;
    private ?Closure $headerEmitter = null;
    private ?Closure $redirectTerminator = null;

    public function withFrontControllerFactory(callable $frontControllerFactory): self
    {
        $clone = clone $this;
        $clone->frontControllerFactory = Closure::fromCallable($frontControllerFactory);

        return $clone;
    }

    public function withHeadersSentChecker(callable $headersSentChecker): self
    {
        $clone = clone $this;
        $clone->headersSentChecker = Closure::fromCallable($headersSentChecker);

        return $clone;
    }

    public function withHeaderEmitter(callable $headerEmitter): self
    {
        $clone = clone $this;
        $clone->headerEmitter = Closure::fromCallable($headerEmitter);

        return $clone;
    }

    public function withRedirectTerminator(callable $redirectTerminator): self
    {
        $clone = clone $this;
        $clone->redirectTerminator = Closure::fromCallable($redirectTerminator);

        return $clone;
    }

    public function build(): ApplicationRunner
    {
        return new ApplicationRunner(
            $this->frontControllerFactory,
            $this->headersSentChecker,
            $this->headerEmitter,
            $this->redirectTerminator
        );
    }
}
