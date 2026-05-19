<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\HeadersSentCheckerInterface;

final class FixedHeadersSentChecker implements HeadersSentCheckerInterface
{
    public function __construct(private bool $headersSent)
    {
    }

    public function headersSent(): bool
    {
        return $this->headersSent;
    }
}
