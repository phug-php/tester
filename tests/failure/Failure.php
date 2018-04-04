<?php

namespace ProjectExample;

use Phug\Tester\TestCase;

class Failure extends TestCase
{
    public function testFailure()
    {
        self::assertTrue(false);
    }
}
