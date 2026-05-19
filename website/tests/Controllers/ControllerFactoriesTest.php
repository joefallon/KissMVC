<?php
declare(strict_types=1);

namespace Tests\Controllers;

use Controllers\IndexController;
use Controllers\IndexControllerFactory;
use Controllers\PageWithParametersController;
use Controllers\PageWithParametersControllerFactory;
use KissMVC\Controller;
use PHPUnit\Framework\TestCase;

final class ControllerFactoriesTest extends TestCase
{
    public function testIndexControllerFactoryCreatesAnIndexController(): void
    {
        $controller = IndexControllerFactory::create();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        self::assertInstanceOf(Controller::class, $controller);
        self::assertInstanceOf(IndexController::class, $controller);
    }

    public function testPageWithParametersControllerFactoryCreatesAParametersController(): void
    {
        $controller = PageWithParametersControllerFactory::create();

        self::assertInstanceOf(Controller::class, $controller);
        self::assertInstanceOf(PageWithParametersController::class, $controller);
    }
}
