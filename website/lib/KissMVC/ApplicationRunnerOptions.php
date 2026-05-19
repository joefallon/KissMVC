<?php declare(strict_types=1);

namespace KissMVC;

final class ApplicationRunnerOptions
{
    public ?FrontControllerFactoryInterface $frontControllerFactory = null;
    public ?HeadersSentCheckerInterface $headersSentChecker = null;
    public ?HeaderEmitterInterface $headerEmitter = null;
    public ?RedirectTerminatorInterface $redirectTerminator = null;
}
