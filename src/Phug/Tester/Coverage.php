<?php

namespace Phug\Tester;

use Phug\Formatter\ElementInterface;
use Phug\Parser\Node\DocumentNode;
use Phug\Parser\NodeInterface;
use Phug\Renderer;
use Phug\Util\SourceLocationInterface;
use SplObjectStorage;

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
     * @var array
     */
    protected $lastCoverageData = null;

    /**
     * @var bool
     */
    protected $started = false;

    /**
     * @var bool
     */
    protected $coverageStoppingAllowed = true;

    /**
     * @var array
     */
    protected $coverage = [];

    /**
     * @var static
     */
    protected static $singleton = null;

    public static function get()
    {
        if (!static::$singleton) {
            static::$singleton = new static();
        }

        return static::$singleton;
    }

    public static function reset()
    {
        static::$singleton = null;
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

    public function emptyCache()
    {
        static::emptyDirectory($this->renderer->getOption('cache_dir'));
    }

    public function removeCache()
    {
        static::removeDirectory($this->renderer->getOption('cache_dir'));
    }

    public function allowCoverageStopping()
    {
        $this->coverageStoppingAllowed = true;
    }

    public function disallowCoverageStopping()
    {
        $this->coverageStoppingAllowed = false;
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
        $this->started = !xdebug_code_coverage_started();
        if ($this->started) {
            xdebug_start_code_coverage();
        }
        // @codeCoverageIgnoreEnd
    }

    public function storeCoverage($data)
    {
        $this->lastCoverageData = $data;
    }

    /**
     * @return array
     */
    public function getLastCoverageData()
    {
        return $this->lastCoverageData;
    }

    protected function getCoverageData()
    {
        if (xdebug_code_coverage_started()) {
            $data = xdebug_get_code_coverage();
            // @codeCoverageIgnoreStart
            if ($this->started && $this->coverageStoppingAllowed) {
                $this->started = false;
                xdebug_stop_code_coverage();
            }
            // @codeCoverageIgnoreEnd

            return $data;
        }

        return static::get()->getLastCoverageData();
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

    protected function recordLocation(SourceLocationInterface $location = null, $covered = 0)
    {
        if ($location) {
            $locationPath = realpath($location->getPath());
            $locationLine = $location->getLine() - 1;
            if (!isset($this->coverage[$locationPath])) {
                $this->coverage[$locationPath] = [];
            }
            if (!isset($this->coverage[$locationPath][$locationLine])) {
                $this->coverage[$locationPath][$locationLine] = [];
            }
            $this->coverage[$locationPath][$locationLine][$location->getOffset() - 1] = $covered;
        }
    }

    protected function listNodes(SplObjectStorage $list, ElementInterface $element)
    {
        $node = $element->getOriginNode();
        if ($node && !($node instanceof DocumentNode)) {
            $list->attach($node);
            $this->recordLocation($node->getSourceLocation());
        }

        foreach ($element->getChildren() as $child) {
            if ($child instanceof ElementInterface) {
                static::listNodes($list, $child);
            }
        }
    }

    protected function countFileNodes($file)
    {
        $document = $this->renderer->getCompiler()->compileFileIntoElement($file);
        $list = new SplObjectStorage();

        $this->listNodes($list, $document);

        return count($list);
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
        $formatter = $this->renderer->getCompiler()->getFormatter();
        $cache = realpath($this->renderer->getOption('cache_dir')).DIRECTORY_SEPARATOR;
        $len = strlen($cache);
        $coveredNodes = [];
        $counts = [];

        foreach ($this->getCoverageData() as $file => $results) {
            if (substr(realpath($file), 0, $len) === $cache) {
                $lines = file($file);
                foreach ($lines as $number => $line) {
                    if (isset($results[$number]) && preg_match('/^\/\/ PUG_DEBUG:(\d+)$/', $line, $match)) {
                        $debugId = intval($match[1]);
                        if ($formatter->debugIdExists($debugId)) {
                            for ($node = $formatter->getNodeFromDebugId($debugId);
                                $node instanceof NodeInterface;
                                $node = $node->getOuterNode() ?: $node->getParent()) {
                                if (!($node instanceof DocumentNode) && ($location = $node->getSourceLocation())) {
                                    $locationPath = $location->getPath();
                                    if (!isset($counts[$locationPath])) {
                                        $counts[$locationPath] = $this->countFileNodes($locationPath);
                                    }
                                    if (!isset($coveredNodes[$locationPath])) {
                                        $coveredNodes[$locationPath] = new SplObjectStorage();
                                    }
                                    if (!isset($coveredNodes[$locationPath][$node])) {
                                        $coveredNodes[$locationPath]->attach($node);
                                    }
                                    $this->recordLocation($location, 1);
                                }
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
                $fileNodesCount = isset($counts[$file]) ? $counts[$file] : $this->countFileNodes($file);
                $fileCoverage = isset($this->coverage[$file]) ? $this->coverage[$file] : [];
                $filePath = $this->getLocationPath($file);
                $html = '';
                $lines = file($file);
                $count = count($lines);
                $pad = strlen(strval($count));
                foreach ($lines as $number => $line) {
                    $id = $number + 1;
                    $html .= '<div><a style="color: gray;" id="L'.$id.'" href="#L'.$id.'">'.
                        str_pad($id, $pad, ' ', STR_PAD_LEFT).
                        ' &nbsp;</a>';
                    $lineCoverage = isset($fileCoverage[$number]) ? $fileCoverage[$number] : [];
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
                        'path'     => $filePath,
                        'coverage' => $html,
                    ]);

                    $this->writeFile($directory.DIRECTORY_SEPARATOR.$filePath.'.html', $html);
                }
                $files[$filePath] = [
                    isset($coveredNodes[$file]) ? count($coveredNodes[$file]) : 0,
                    $fileNodesCount
                ];
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
    public function createRenderer($renderer, $extensions, $paths, $cacheDirectory = null)
    {
        if (is_string($renderer)) {
            $cache = $cacheDirectory ?: sys_get_temp_dir().DIRECTORY_SEPARATOR.'pug-cache-'.mt_rand(0, 9999999);
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
