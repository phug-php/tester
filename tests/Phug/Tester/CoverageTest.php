<?php

namespace Phug\Test\Tester;

use Phug\Tester\Coverage;

/**
 * @coversDefaultClass \Phug\Tester\Coverage
 */
class CoverageTest extends AbstractTesterCaseTest
{
    /**
     * @covers ::reset
     * @covers ::get
     */
    public function testGet()
    {
        Coverage::reset();
        $one = Coverage::get();
        $two = Coverage::get();

        self::assertInstanceOf(Coverage::class, $one);
        self::assertSame($one, $two);
    }
}
