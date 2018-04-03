<?php

namespace Phug\Tester;

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
     * @var PhpunitCommand
     */
    private $command;

    public function __construct($vendor)
    {
        $this->vendor = $vendor;
    }

    public function getVendorScript($script)
    {
        return realpath($this->vendor."/$script");
    }

    protected function runPhpunit($arguments)
    {
        if (!$this->command) {
            $this->command = new PhpunitCommand();
        }

        return !$this->command->run($arguments, false);
    }

    protected function exec($arguments)
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

    public function run($arguments, $exit = true)
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
