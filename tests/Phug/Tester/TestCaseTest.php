<?php

namespace Phug\Test\Tester;

use Phug\Tester\TestCase;

class TestCaseTest extends TestCase
{
    public function testElse()
    {
        $this->runXdebug();
        $this->renderer->renderFile(__DIR__.'/../../../tests/index.pug');
        var_dump(array_keys($this->getCoverage()));
        exit;
    }
}
