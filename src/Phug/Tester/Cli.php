<?php

namespace Phug\Tester;

class Cli
{
    /**
     * @var string
     */
    private $vendor;

    /**
     * @var PhpunitCommand
     */
    private $command;

    protected static $coverageOptions = [
        '--coverage-clover' => false,
        '--coverage-crap4j' => false,
        '--coverage-html'   => false,
        '--coverage-php'    => false,
        '--coverage-text'   => true,
        '--coverage-xml'    => false,
    ];

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
        for ($i = 1; $i < count($arguments); $i++) {
            $arg = $arguments[$i];

            if ($arg === '--pug-coverage-text') {
                $textCoverage = true;

                continue;
            }
            if ($arg === '--pug-coverage-html') {
                $htmlCoverage = $arguments[++$i];

                continue;
            }
            if (substr($arg, 0, 20) === '--pug-coverage-html=') {
                $htmlCoverage = substr($arg, 20);

                continue;
            }
            if ($arg === '--pug-coverage-threshold') {
                $coverageThreshold = $arguments[++$i];

                continue;
            }
            if (substr($arg, 0, 25) === '--pug-coverage-threshold=') {
                $coverageThreshold = substr($arg, 25);

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

        exit($result ? 0 : 1);
    }
}
