<?php declare(strict_types=1);

namespace KissMVC;

interface FrontControllerFactoryInterface
{
    public function create(): FrontController;
}
