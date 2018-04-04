<?php

namespace Phug\Test\Tester;

use Phug\Tester\Cli;

/**
 * @coversDefaultClass \Phug\Tester\Cli
 */
class CliTest extends AbstractTesterCaseTest
{
    /**
     * @covers ::__construct
     * @covers ::getVendorScript
     */
    public function testGetVendorScript()
    {
        $cli = new Cli(__DIR__.'/../../../vendor');

        self::assertSame(
            realpath(__DIR__.'/../../../vendor/phpunit/phpunit/phpunit'),
            $cli->getVendorScript('phpunit/phpunit/phpunit')
        );
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::__construct
     * @covers ::runPhpunit
     * @covers ::exec
     * @covers ::run
     * @covers \Phug\Tester\PhpunitCommand::createRunner
     * @covers \Phug\Tester\Coverage::getPaths
     * @covers \Phug\Tester\Coverage::getLocationPath
     */
    public function testRun()
    {
        $base = __DIR__.'/../../..';
        $vendor = "$base/vendor";
        $cli = new Cli($vendor);
        $this->renderFile('loop.pug');

        $params = ["$base/phug-tester", '-c', "$vendor/phug/dev-tool/config"];
        ob_start();
        $run = $cli->run($params, false);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertRegExp('/phug[\\/\\\\]dev-tool[\\/\\\\]config[\\/\\\\]phpunit\.xml/', $output);
        self::assertTrue($run);
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::__construct
     * @covers ::runPhpunit
     * @covers ::exec
     * @covers ::run
     * @covers \Phug\Tester\PhpunitCommand::createRunner
     * @covers \Phug\Tester\Coverage::getPaths
     * @covers \Phug\Tester\Coverage::getLocationPath
     * @covers \Phug\Tester\Coverage::setThreshold
     * @covers \Phug\Tester\Coverage::isThresholdReached
     */
    public function testThreshold()
    {
        $base = __DIR__.'/../../..';
        $cli = new Cli("$base/vendor");
        $this->renderFile('loop.pug');

        $params = ["$base/phug-tester", '-c', "$base/example", '--pug-coverage-threshold=10'];
        ob_start();
        $run = $cli->run($params, false);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertContains('Expected threshold 10% reached.', $output);
        self::assertTrue($run);

        $params = ["$base/phug-tester", '-c', "$base/example", '--pug-coverage-threshold=20'];
        ob_start();
        $run = $cli->run($params, false);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertContains('Expected threshold 20% not reached.', $output);
        self::assertFalse($run);

        $params = ["$base/phug-tester", '-c', "$base/example", '--pug-coverage-threshold', '10'];
        ob_start();
        $run = $cli->run($params, false);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertContains('Expected threshold 10% reached.', $output);
        self::assertTrue($run);

        $params = ["$base/phug-tester", '-c', "$base/example", '--pug-coverage-threshold', '20'];
        ob_start();
        $run = $cli->run($params, false);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertContains('Expected threshold 20% not reached.', $output);
        self::assertFalse($run);
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::exec
     */
    public function testHtmlCoverage()
    {
        $base = __DIR__.'/../../..';
        $cli = new Cli("$base/vendor");
        $this->renderFile('loop.pug');
        $directory = sys_get_temp_dir().'/pug-coverage-'.mt_rand(0, 999999);

        static::removeDirectory($directory);
        $params = ["$base/phug-tester", '-c', "$base/example", "--pug-coverage-html=$directory"];
        ob_start();
        $run = $cli->run($params, false);
        ob_end_clean();
        self::assertFileExists("$directory/loop.pug.html");
        self::assertTrue($run);

        static::removeDirectory($directory);
        $params = ["$base/phug-tester", '-c', "$base/example", '--pug-coverage-html', $directory];
        ob_start();
        $run = $cli->run($params, false);
        ob_end_clean();
        self::assertFileExists("$directory/loop.pug.html");
        self::assertTrue($run);
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::exec
     */
    public function testUnitTestsFailure()
    {
        $base = __DIR__.'/../../..';
        $cli = new Cli("$base/vendor");
        $params = ["$base/phug-tester", '-c', "$base/tests/failure", '--pug-coverage-text'];
        ob_start();
        $run = $cli->run($params, false);
        ob_end_clean();
        self::assertFalse($run);
    }

    /**
     * @throws \Phug\RendererException
     * @covers ::__construct
     * @covers ::runPhpunit
     * @covers ::exec
     * @covers ::run
     * @covers \Phug\Tester\PhpunitCommand::createRunner
     * @covers \Phug\Tester\Coverage::getPaths
     * @covers \Phug\Tester\Coverage::getLocationPath
     */
    public function testTextCoverage()
    {
        $base = __DIR__.'/../../..';
        $vendor = "$base/vendor";
        $cli = new Cli($vendor);
        $this->renderFile('loop.pug');

        $params = ["$base/phug-tester", '-c', "$vendor/phug/dev-tool/config", '--pug-coverage-text'];
        ob_start();
        $run = $cli->run($params, false);
        $output = ob_get_contents();
        ob_end_clean();
        self::assertTrue($run);
        self::assertContains('indent.pug', $output);
        self::assertContains('index.pug', $output);
        self::assertContains('loop.pug', $output);
    }
}
