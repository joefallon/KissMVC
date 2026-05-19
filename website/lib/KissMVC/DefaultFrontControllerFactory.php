<?php declare(strict_types=1);

namespace KissMVC;

final class DefaultFrontControllerFactory implements FrontControllerFactoryInterface
{
    public function create(): FrontController
    {
        return new FrontController();
    }
}
