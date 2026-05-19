<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\FrontController;

final class RecordingFrontController extends FrontController
{
    public bool $wasRouted = false;

    public function routeRequest(): void
    {
        $this->wasRouted = true;
    }
}
