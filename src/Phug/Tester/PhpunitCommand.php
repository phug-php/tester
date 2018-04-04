<?php

namespace Phug\Tester;

use PHPUnit\TextUI\Command;

class PhpunitCommand extends Command
{
    protected function createRunner()
    {
        return new TestRunnerInterceptor($this->arguments['loader']);
    }
}
