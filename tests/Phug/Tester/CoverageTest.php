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

    /**
     * @covers ::isCoverageAllowedToStop
     * @covers ::allowCoverageStopping
     * @covers ::disallowCoverageStopping
     */
    public function testAllowCoverageStopping()
    {
        $coverage = new Coverage();
        self::assertTrue($coverage->isCoverageAllowedToStop());
        $coverage->disallowCoverageStopping();
        self::assertFalse($coverage->isCoverageAllowedToStop());
        $coverage->allowCoverageStopping();
        self::assertTrue($coverage->isCoverageAllowedToStop());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatus()
    {
        self::assertSame('success', Coverage::getStatus(10, 5));
        self::assertSame('success', Coverage::getStatus(5, 5));
        self::assertSame('success', Coverage::getStatus(9, 10));
        self::assertSame('warning', Coverage::getStatus(3, 5));
        self::assertSame('warning', Coverage::getStatus(3, 6));
        self::assertSame('danger', Coverage::getStatus(2, 5));
        self::assertSame('danger', Coverage::getStatus(1, 5));
        self::assertSame('danger', Coverage::getStatus(0, 5));
        self::assertSame('danger', Coverage::getStatus(0, 0));
    }
}
