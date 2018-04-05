<?php

namespace Phug\Test\Tester;

use Phug\Tester\Coverage;

/**
 * @coversDefaultClass \Phug\Tester\TestCaseTrait
 */
class TestCaseTest extends AbstractTesterCaseTest
{
    /**
     * @covers ::getPaths
     */
    public function testGetPaths()
    {
        self::assertSame(['views'], $this->getPaths(true));
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::renderFile
     */
    public function testRenderFileOptions()
    {
        $html = $this->renderFile('indent.pug', [], ['pretty' => false]);
        self::assertFalse($this->renderer->getCompiler()->getFormatter()->getOption('pretty'));
        self::assertNotContains('  ', $html);
        self::emptyDirectory($this->renderer->getOption('cache_dir'));
        $html = $this->renderFile('indent.pug', [], ['pretty' => true]);
        self::assertTrue($this->renderer->getCompiler()->getFormatter()->getOption('pretty'));
        self::assertContains('  ', $html);
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::renderFile
     * @covers ::getExtensions
     * @covers ::getRenderer
     * @covers ::setUp
     * @covers ::getRendererOptions
     * @covers ::setUpCoverage
     * @covers ::tearDown
     * @covers ::tearDownCoverage
     * @covers \Phug\Tester\Cli::<public>
     * @covers \Phug\Tester\Coverage::<public>
     * @covers \Phug\Tester\Coverage::recordLocation
     * @covers \Phug\Tester\Coverage::listNodes
     * @covers \Phug\Tester\Coverage::countFileNodes
     * @covers \Phug\Tester\Coverage::getTemplateFile
     * @covers \Phug\Tester\Coverage::writeFile
     * @covers \Phug\Tester\Coverage::writeSummaries
     * @covers \Phug\Tester\Coverage::storeCoverage
     * @covers \Phug\Tester\Coverage::getCoverageData
     * @covers \Phug\Tester\Coverage::getLastCoverageData
     * @covers \Phug\Tester\Coverage::emptyDirectory
     * @covers \Phug\Tester\Coverage::addEmptyDirectory
     * @covers \Phug\Tester\Coverage::removeDirectory
     * @covers \Phug\Tester\PhpunitCommand::createRunner
     */
    public function testCoverage()
    {
        $directory = sys_get_temp_dir().'/pug-tester-'.mt_rand(0, 999999);
        $coverage = Coverage::get();
        $coverage->runXDebug();
        $coverage->disallowCoverageStopping();
        self::assertTrue(xdebug_code_coverage_started());

        $html = $this->renderFile('index.pug');
        self::assertContains('Bar', $html);
        self::assertNotContains('Foo', $html);

        $coverage->storeCoverage(xdebug_get_code_coverage());
        $data = $coverage->getLastCoverageData();
        $cachedFiles = array_values(array_filter(array_keys($data), function ($file) {
            return strpos($file, 'pug-cache-') !== false;
        }));
        self::assertCount(1, $cachedFiles);
        self::assertSame(1, $data[$cachedFiles[0]][2]);
        self::assertSame(1, $data[$cachedFiles[0]][5]);

        ob_start();
        $coverage->dumpCoverage(true, $directory);
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertContains('75.0%', $contents);

        $file = "$directory/index.pug.html";
        self::assertFileExists($file);

        $contents = file_get_contents($file);
        self::assertRegExp('/<span\s+class="uncovered chunk">\s*Foo\s*<\/span>/', $contents);
        self::assertContains('index.pug', $contents);

        $file = "$directory/directory/index.html";
        self::assertFileExists($file);

        $contents = file_get_contents($file);
        self::assertContains('a.pug', $contents);
        self::assertContains('b.pug', $contents);

        self::removeDirectory($directory);

        $cache = sys_get_temp_dir().'/pug-cache-'.mt_rand(0, 999999);
        $coverage->createRenderer($this->getRenderer(), $this->getRendererOptions($cache));
        touch("$cache/foo");
        mkdir("$cache/bar");
        touch("$cache/bar/foo");
        self::assertFileExists("$cache/foo");
        self::assertFileExists("$cache/bar/foo");
        $coverage->createRenderer($this->getRenderer(), $this->getRendererOptions($cache));
        self::assertFileNotExists("$cache/foo");
        self::assertFileNotExists("$cache/bar/foo");
        touch("$cache/foo");
        mkdir("$cache/bar");
        touch("$cache/bar/foo");

        $coverage->removeCache();

        self::assertFileNotExists("$cache/foo");
        self::assertFileNotExists("$cache/bar/foo");
        self::assertFileNotExists($cache);

        $coverage->emptyCache();

        self::assertFileNotExists($cache);
    }
}
