<?php declare(strict_types=1);

namespace KissMVC;

interface RequestParametersProviderInterface
{
    public function getRequestParameters(): ?array;
}
