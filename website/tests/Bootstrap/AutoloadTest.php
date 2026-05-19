<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AutoloadTest extends TestCase
{
    public function testProjectClassesAutoload(): void
    {
        foreach ([
            'Bootstrapper',
            'KissMVC\Application',
            'KissMVC\Controller',
            'KissMVC\FrontController',
            'Controllers\IndexControllerFactory',
            'Controllers\PageWithParametersControllerFactory',
        ] as $className) {
            self::assertTrue(class_exists($className), $className . ' should autoload');
        }
    }
}
