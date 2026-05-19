<?php declare(strict_types=1);

namespace KissMVC;

final class NativeHeadersSentChecker implements HeadersSentCheckerInterface
{
    public function headersSent(): bool
    {
        return headers_sent();
    }
}
