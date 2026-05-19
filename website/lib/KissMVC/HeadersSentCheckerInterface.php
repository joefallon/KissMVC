<?php declare(strict_types=1);

namespace KissMVC;

interface HeadersSentCheckerInterface
{
    public function headersSent(): bool;
}
