<?php declare(strict_types=1);

namespace Tests\Support;

use KissMVC\FrontController;
use KissMVC\FrontControllerFactoryInterface;

final class RecordingFrontControllerFactory implements FrontControllerFactoryInterface
{
    public function __construct(private FrontController $frontController)
    {
    }

    public function create(): FrontController
    {
        return $this->frontController;
    }
}
