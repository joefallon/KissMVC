<?php declare(strict_types=1);

namespace KissMVC;

final class FrontControllerOptions
{
    public ?RequestParametersProviderInterface $requestParametersProvider = null;
    public ?RouteResolverInterface $routeResolver = null;
    public ?HeadersSentCheckerInterface $headersSentChecker = null;
    public ?HeaderEmitterInterface $headerEmitter = null;
}
