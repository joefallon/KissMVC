<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\RequestParametersProviderInterface;

final class FixedRequestParametersProvider implements RequestParametersProviderInterface
{
    public function __construct(private ?array $requestParameters)
    {
    }

    public function getRequestParameters(): ?array
    {
        return $this->requestParameters;
    }
}
