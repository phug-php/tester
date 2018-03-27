<?php

namespace Phug\Test\Tester;

use Phug\Tester\TestCase;

/**
 * @coversDefaultClass Phug\Tester\TestCase
 */
class TestCaseTest extends TestCase
{
    protected function getPaths()
    {
        return [__DIR__.'/../../../tests/views'];
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::renderFile
     * @covers ::getPaths
     * @covers ::getExtensions
     * @covers ::getRenderer
     * @covers ::setUp
     * @covers \Phug\Tester\Cli::<public>
     * @covers \Phug\Tester\Coverage::<public>
     */
    public function testElse()
    {
        $html = $this->renderFile('index.pug');
        self::assertContains('Bar', $html);
        self::assertNotContains('Foo', $html);
//        $this->renderer->renderFile('index.pug', ['foo' => true]);
    }
}
