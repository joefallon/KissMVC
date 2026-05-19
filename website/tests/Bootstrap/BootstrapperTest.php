<?php
declare(strict_types=1);

namespace Tests\Bootstrap;

use Bootstrapper;
use PHPUnit\Framework\TestCase;

final class BootstrapperTest extends TestCase
{
    public function testBootstrapCanBeCalled(): void
    {
        Bootstrapper::bootstrap();

        self::assertTrue(true);
    }
}
