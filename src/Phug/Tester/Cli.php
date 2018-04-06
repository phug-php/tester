<?php

namespace Phug\Tester;

use PHPUnit\TextUI\Command;

class Cli
{
    /**
     * @const string
     */
    const PUG_COVERAGE_TEXT = '--pug-coverage-text';

    /**
     * @const string
     */
    const PUG_COVERAGE_HTML = '--pug-coverage-html';

    /**
     * @const string
     */
    const PUG_COVERAGE_THRESHOLD = '--pug-coverage-threshold';

    /**
     * @var string
     */
    private $vendor;

    /**
     * @var Command
     */
    private $command;

    public function __construct(string $vendor)
    {
        $this->vendor = $vendor;
    }

    public function getVendorScript(string $script) : string
    {
        return realpath($this->vendor."/$script");
    }

    /**
     * @param array $arguments
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    protected function runPhpunit(array $arguments) : bool
    {
        if (!$this->command) {
            $this->command = new Command();
        }

        return !$this->command->run($arguments, false);
    }

    /**
     * @param array $arguments
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    protected function exec(array $arguments) : bool
    {
        $phpunit = $this->getVendorScript('phpunit/phpunit/phpunit');
        $phpunitArguments = [$phpunit];
        $textCoverage = false;
        $htmlCoverage = null;
        $coverageThreshold = null;
        $htmlValueArg = static::PUG_COVERAGE_HTML.'=';
        $htmlValueArgLength = strlen($htmlValueArg);
        $thresholdValueArg = static::PUG_COVERAGE_THRESHOLD.'=';
        $thresholdValueArgLength = strlen($thresholdValueArg);
        for ($i = 1; $i < count($arguments); $i++) {
            $arg = $arguments[$i];

            if ($arg === static::PUG_COVERAGE_TEXT) {
                $textCoverage = true;

                continue;
            }
            if ($arg === static::PUG_COVERAGE_HTML) {
                $htmlCoverage = $arguments[++$i];

                continue;
            }
            if (substr($arg, 0, $htmlValueArgLength) === $htmlValueArg) {
                $htmlCoverage = substr($arg, $htmlValueArgLength);

                continue;
            }
            if ($arg === static::PUG_COVERAGE_THRESHOLD) {
                $coverageThreshold = $arguments[++$i];

                continue;
            }
            if (substr($arg, 0, $thresholdValueArgLength) === $thresholdValueArg) {
                $coverageThreshold = substr($arg, $thresholdValueArgLength);

                continue;
            }

            $phpunitArguments[] = $arg;
        }
        if (!($textCoverage || $htmlCoverage || $coverageThreshold)) {
            return $this->runPhpunit($arguments);
        }

        $coverage = Coverage::get();
        $coverage->setThreshold($coverageThreshold);
        $coverage->runXDebug();

        if (!$this->runPhpunit($phpunitArguments)) {
            return false;
        }

        $coverage->dumpCoverage($textCoverage, $htmlCoverage);

        return $coverage->isThresholdReached();
    }

    /**
     * @param array $arguments
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    public function run(array $arguments, $exit = true) : bool
    {
        $result = $this->exec($arguments);

        if (!$exit) {
            return $result;
        }

        // @codeCoverageIgnoreStart
        exit($result ? 0 : 1);
        // @codeCoverageIgnoreEnd
    }
}
