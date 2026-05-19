<?php declare(strict_types=1);

namespace KissMVC;

final class NativeHeaderEmitter implements HeaderEmitterInterface
{
    public function emit(string $header, bool $replace = true, ?int $responseCode = null): void
    {
        if($responseCode === null)
        {
            header($header, $replace);

            return;
        }

        header($header, $replace, $responseCode);
    }
}
