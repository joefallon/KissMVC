<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\RedirectTerminatorInterface;

final class RecordingRedirectTerminator implements RedirectTerminatorInterface
{
    /** @var array<int, string> */
    public array $urls = [];

    public function terminate(string $url): void
    {
        $this->urls[] = $url;
    }
}
