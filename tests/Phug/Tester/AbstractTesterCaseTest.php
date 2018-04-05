<?php

namespace Phug\Test\Tester;

use Phug\Tester\TestCase;

abstract class AbstractTesterCaseTest extends TestCase
{
    protected function getPaths(bool $parent = false)
    {
        return $parent ? parent::getPaths() : [__DIR__.'/../../../example/views'];
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
        if (file_exists($dir)) {
            rmdir($dir);
        }
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
