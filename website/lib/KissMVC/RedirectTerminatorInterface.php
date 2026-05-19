<?php declare(strict_types=1);

namespace KissMVC;

interface RedirectTerminatorInterface
{
    public function terminate(string $url): void;
}
