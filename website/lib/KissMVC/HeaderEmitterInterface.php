<?php declare(strict_types=1);

namespace KissMVC;

interface HeaderEmitterInterface
{
    public function emit(string $header, bool $replace = true, ?int $responseCode = null): void;
}
