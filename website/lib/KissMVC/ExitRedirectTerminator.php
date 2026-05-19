<?php declare(strict_types=1);

namespace KissMVC;

use JetBrains\PhpStorm\NoReturn;

/** @codeCoverageIgnore */
final class ExitRedirectTerminator implements RedirectTerminatorInterface
{
    #[NoReturn]
    public function terminate(string $url): void
    {
        exit;
    }
}
