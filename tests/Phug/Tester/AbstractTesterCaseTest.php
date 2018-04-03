<?php

namespace Phug\Test\Tester;

use Phug\Tester\TestCase;

abstract class AbstractTesterCaseTest extends TestCase
{
    protected function getPaths($parent = false)
    {
        return $parent ? parent::getPaths() : [__DIR__.'/../../../tests/views'];
    }

    protected static function emptyDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir.'/'.$file;
                if (is_dir($path)) {
                    static::emptyDirectory($path);
                    rmdir($path);

                    continue;
                }

                unlink($path);
            }
        }
    }

    protected static function removeDirectory($dir)
    {
        static::emptyDirectory($dir);
        rmdir($dir);
    }

    protected static function addEmptyDirectory($dir)
    {
        if (file_exists($dir)) {
            is_dir($dir) ? static::emptyDirectory($dir) : unlink($dir);

            return;
        }

        mkdir($dir, 0777, true);
    }
}
