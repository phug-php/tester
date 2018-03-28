<?php

namespace Phug\Tester;

use Phug\Parser\NodeInterface;
use Phug\Renderer;
use Phug\Util\SourceLocationInterface;

class Coverage
{
    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var float
     */
    protected $threshold = 0;

    /**
     * @var float
     */
    protected $lastCoverageRate = 0;

    /**
     * @var static
     */
    protected static $coverage = null;

    public static function get()
    {
        if (!static::$coverage) {
            static::$coverage = new static();
        }

        return static::$coverage;
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

    protected function getPaths()
    {
        return $this->renderer->getOption('paths');
    }

    public function runXDebug()
    {
        // @codeCoverageIgnoreStart
        if (!function_exists('xdebug_code_coverage_started')) {
            throw new \BadFunctionCallException('You need to install XDebug to use coverage feature.');
        }
        // @codeCoverageIgnoreEnd
        if (!xdebug_code_coverage_started()) {
            xdebug_start_code_coverage();
        }
    }

    protected static function getCoverageData()
    {
        var_dump(array_keys(TestRunnerInterceptor::getLastCoverage()->getData(true)), xdebug_get_code_coverage());
        exit;
        if (xdebug_code_coverage_started()) {
            $data = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();

            return $data;
        }

        return TestRunnerInterceptor::getLastCoverage()->getData(true);
    }

    private function getLocationPath($path)
    {
        foreach ($this->getPaths() as $base) {
            $realBase = realpath($base);
            if ($realBase) {
                $realPath = realpath($path);
                if ($realPath) {
                    $realBase .= DIRECTORY_SEPARATOR;
                    $len = strlen($realBase);
                    if (substr($realPath, 0, $len) === $realBase) {
                        return substr($realPath, $len);
                    }
                }
            }
        }

        return $path;
    }

    protected function getTemplateFile($file, $vars)
    {
        $__php = file_get_contents(__DIR__."/../../template/$file");
        extract($vars);
        ob_start();
        eval("?>$__php");

        return ob_get_clean();
    }

    protected function writeFile($path, $contents)
    {
        $base = dirname($path);
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }

        return is_int(file_put_contents($path, $contents));
    }

    public function dumpCoverage($output = false, $directory = null)
    {
        if ($directory) {
            static::addEmptyDirectory($directory);
            $this->writeFile(
                $directory.'/css/style.css',
                file_get_contents(__DIR__.'/../../template/css/style.css')
            );
            $this->writeFile(
                $directory.'/css/bootstrap.min.css',
                file_get_contents(__DIR__.'/../../template/css/bootstrap.min.css')
            );
        }
        if ($output) {
            echo "\n| Coverage:\n|\n";
        }
        $coverage = [];
        $formatter = $this->renderer->getCompiler()->getFormatter();
        $cache = realpath($this->renderer->getOption('cache_dir')).DIRECTORY_SEPARATOR;
        $len = strlen($cache);

        $recordLocation = function (SourceLocationInterface $location = null, $covered = 0) use (&$coverage) {
            if ($location) {
                $locationPath = realpath($location->getPath());
                $locationLine = $location->getLine() - 1;
                if (!isset($coverage[$locationPath])) {
                    $coverage[$locationPath] = [];
                }
                if (!isset($coverage[$locationPath][$locationLine])) {
                    $coverage[$locationPath][$locationLine] = [];
                }
                $coverage[$locationPath][$locationLine][$location->getOffset() - 1] = $covered;
            }
        };

        $nodes = new \SplObjectStorage();
        $coveredNodes = new \SplObjectStorage();
        for ($debugId = 0; $formatter->debugIdExists($debugId); $debugId++) {
            $node = $formatter->getNodeFromDebugId($debugId);
            $nodes->attach($node);
            $recordLocation($node->getSourceLocation());
        }

        foreach (static::getCoverageData() as $file => $results) {
            if (substr(realpath($file), 0, $len) === $cache) {
                $lines = file($file);
                foreach ($lines as $number => $line) {
                    if (isset($results[$number]) && preg_match('/^\/\/ PUG_DEBUG:(\d+)$/', $line, $match)) {
                        $debugId = intval($match[1]);
                        if ($formatter->debugIdExists($debugId)) {
                            for (
                                $node = $formatter->getNodeFromDebugId($debugId);
                                $node instanceof NodeInterface;
                                $node = $node->getOuterNode() ?: $node->getParent()
                            ) {
                                if (isset($nodes[$node])) {
                                    $coveredNodes->attach($node);
                                }
                                $recordLocation($node->getSourceLocation(), 1);
                            }
                        }
                    }
                }
            }
        }

        $files = [];
        foreach ($this->getPaths() as $path) {
            foreach ($this->renderer->scanDirectory($path) as $file) {
                $coveredState = 0;
                $file = realpath($file);
                $coverage = isset($coverage[$file]) ? $coverage[$file] : [];
                $path = $this->getLocationPath($file);
                $html = '';
                $lines = file($file);
                $count = count($lines);
                $pad = strlen(strval($count));
                foreach ($lines as $number => $line) {
                    $id = $number + 1;
                    $html .= '<div><a style="color: gray;" id="L'.$id.'" href="#L'.$id.'">'.
                        str_pad($id, $pad, ' ', STR_PAD_LEFT).
                        ' &nbsp;</a>';
                    $lineCoverage = isset($coverage[$number]) ? $coverage[$number] : [];
                    $currentOffset = 0;
                    foreach ($lineCoverage as $offset => $covered) {
                        if ($offset > $currentOffset && $coveredState !== $covered) {
                            $class = $coveredState ? 'covered' : 'uncovered';
                            $html .= '<span class="'.$class.' chunk">'.
                                mb_substr($line, $currentOffset, $offset).
                                '</span>';
                            $currentOffset = $offset;
                        }
                        $coveredState = $covered;
                    }
                    if (mb_strlen($line) > $currentOffset) {
                        $class = $coveredState ? 'covered' : 'uncovered';
                        $html .= '<span class="'.$class.' chunk">'.
                            mb_substr($line, $currentOffset).
                            '</span>';
                    }
                    $html .= '</div>';
                }
                if ($directory) {
                    $html = $this->getTemplateFile('file.html', [
                        'cssPath'  => 'css',
                        'path'     => $path,
                        'coverage' => $html,
                    ]);

                    $this->writeFile($directory.DIRECTORY_SEPARATOR.$path.'.html', $html);
                }
                $files[$path] = [count($coveredNodes), count($nodes)];
            }
        }
        $pad = max(array_map(function ($path) {
            return strlen($path);
        }, array_keys($files))) + 3;
        $coveredNodes = 0;
        $nodes = 0;
        foreach ($files as $file => $stats) {
            list($_coveredNodes, $_nodes) = $stats;
            $coveredNodes += $_coveredNodes;
            $nodes += $_nodes;
            if ($output) {
                echo '| '.str_pad($file, $pad, ' ', STR_PAD_RIGHT).
                    str_pad($_coveredNodes, 3, ' ', STR_PAD_LEFT).' / '.
                    str_pad($_nodes, 3, ' ', STR_PAD_LEFT).'   '.
                    str_pad(
                        number_format($_coveredNodes * 100 / max(1, $_nodes), 1),
                        5,
                        ' ',
                        STR_PAD_LEFT
                    )."%\n";
            }
        }
        $this->lastCoverageRate = $coveredNodes * 100 / max(1, $nodes);
        if ($output) {
            echo '|'.str_repeat('-', $pad + 19)."\n| ".
                str_pad('Total', $pad, ' ', STR_PAD_RIGHT).
                str_pad($coveredNodes, 3, ' ', STR_PAD_LEFT).' / '.
                str_pad($nodes, 3, ' ', STR_PAD_LEFT).'   '.
                str_pad(
                    number_format($this->lastCoverageRate, 1),
                    5,
                    ' ',
                    STR_PAD_LEFT
                )."%\n\n";
        }
    }

    /**
     * @return Renderer
     */
    public function createRenderer($renderer, $extensions, $paths)
    {
        if (is_string($renderer)) {
            $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-cache-'.mt_rand(0, 9999999);
            static::addEmptyDirectory($cache);
            $renderer = new $renderer([
                'extensions' => $extensions,
                'paths'      => $paths,
                'debug'      => true,
                'cache_dir'  => realpath($cache),
            ]);
        }

        $this->renderer = $renderer;

        return $renderer;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = floatval($threshold);
    }

    public function isThresholdReached()
    {
        if ($this->threshold) {
            echo "\nOverall coverage is {$this->lastCoverageRate}%.\n";
            echo "\nExpected threshold {$this->threshold}%";

            if ($this->lastCoverageRate < $this->threshold) {
                echo " not reached.\n";

                return false;
            }

            echo " reached.\n";
        }

        return true;
    }
}
