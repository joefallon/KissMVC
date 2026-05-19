<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\HeaderEmitterInterface;

final class RecordingHeaderEmitter implements HeaderEmitterInterface
{
    /** @var array<int, array{0: string, 1: bool, 2: int|null}> */
    public array $headers = [];

    public function emit(string $header, bool $replace = true, ?int $responseCode = null): void
    {
        $this->headers[] = [$header, $replace, $responseCode];
    }
}
