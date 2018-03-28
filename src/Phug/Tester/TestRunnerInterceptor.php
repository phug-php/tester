<?php

namespace Phug\Tester;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestResult;
use PHPUnit\TextUI\TestRunner;

class TestRunnerInterceptor extends TestRunner
{
    /**
     * @var TestResult
     */
    protected static $lastResult;

    public static function getLastCoverage()
    {
        return static::$lastResult->getCodeCoverage();
    }

    public function doRun(Test $suite, array $arguments = [], $exit = true)
    {
        static::$lastResult = parent::doRun($suite, $arguments, $exit);

        return static::$lastResult;
    }
}
