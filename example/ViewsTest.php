<?php

namespace ProjectExample;

use Phug\Tester\TestCase;

class ViewsTest extends TestCase
{
    protected function getPaths()
    {
        return [__DIR__.'/views'];
    }

    public function testIndex()
    {
        $html = $this->renderFile('index.pug');
        self::assertContains('Bar', $html);
        self::assertNotContains('Foo', $html);
    }
}
