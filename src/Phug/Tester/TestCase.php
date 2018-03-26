<?php

namespace Phug\Tester;

use Phug\Renderer;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $renderer;

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

    protected function runXdebug()
    {
        xdebug_start_code_coverage();
    }

    protected function getCoverage()
    {
        $coverage = [];
        $cache = $this->renderer->getOption('cache_dir').DIRECTORY_SEPARATOR;
        $len = strlen($cache);
        foreach (xdebug_get_code_coverage() as $file => $results) {
            if (substr(realpath($file), 0, $len) === $cache) {
                $coverage[$file] = $results;
            }
        }

        return $coverage;
    }

    /**
     * @throws \Phug\RendererException
     */
    protected function setUp()
    {
        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-cache-'.mt_rand(0, 9999999);
        static::addEmptyDirectory($cache);

        $this->renderer = new Renderer([
            'cache_dir' => realpath($cache),
        ]);
    }
}
